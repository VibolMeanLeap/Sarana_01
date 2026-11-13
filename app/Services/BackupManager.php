<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;
use ZipArchive;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use FilesystemIterator;
use Throwable;

class BackupManager
{
    private const ARCHIVE_PREFIX = 'coffee_backup_';

    /** Managed writable/upload directories included in a full application backup. */
    private const MANAGED_DIRECTORIES = [
        'public/images' => 'public/images',
        'public/uploads' => 'public/uploads',
        'storage/app/public' => 'storage/app/public',
    ];

    public function create(string $type = 'manual'): array
    {
        $this->ensureZipExtension();
        $this->ensureBackupDirectory();

        $connection = $this->databaseConnection();
        $workDirectory = $this->backupRoot().DIRECTORY_SEPARATOR.'.work_'.Str::uuid();
        File::ensureDirectoryExists($workDirectory);

        $sqlPath = $workDirectory.DIRECTORY_SEPARATOR.'database.sql';
        $archiveName = self::ARCHIVE_PREFIX.now()->format('Ymd_His').'_'.$this->safeType($type).'_'.Str::lower(Str::random(8)).'.zip';
        $archivePath = $this->backupRoot().DIRECTORY_SEPARATOR.$archiveName;

        try {
            $this->dumpDatabase($connection, $sqlPath);

            $fileManifest = $this->collectApplicationFiles();
            $databaseManifest = [
                'entry' => 'database.sql',
                'size' => (int) filesize($sqlPath),
                'sha256' => $this->sha256File($sqlPath),
            ];

            $manifest = [
                'format' => 'sarana-coffee-backup',
                'format_version' => 1,
                'application' => (string) config('app.name', 'Coffee Shop'),
                'created_at' => now()->toIso8601String(),
                'type' => $this->safeType($type),
                'database' => [
                    'driver' => $connection['driver'],
                    'name' => $connection['database'],
                ],
                'database_dump' => $databaseManifest,
                'files' => $fileManifest,
            ];

            $zip = new ZipArchive();
            $openResult = $zip->open($archivePath, ZipArchive::CREATE | ZipArchive::OVERWRITE);
            if ($openResult !== true) {
                throw new RuntimeException('Could not create the backup ZIP archive. Check that the backup folder is writable.');
            }

            try {
                if (! $zip->addFile($sqlPath, 'database.sql')) {
                    throw new RuntimeException('Could not add the database dump to the backup archive.');
                }

                foreach ($fileManifest as $file) {
                    $source = $file['source'];
                    if (! $zip->addFile($source, $file['entry'])) {
                        throw new RuntimeException('Could not add an application file to the backup archive: '.$file['entry']);
                    }
                }

                $publicManifest = [
                    'format' => $manifest['format'],
                    'format_version' => $manifest['format_version'],
                    'application' => $manifest['application'],
                    'created_at' => $manifest['created_at'],
                    'type' => $manifest['type'],
                    'database' => $manifest['database'],
                    'database_dump' => $manifest['database_dump'],
                    'files' => array_map(fn (array $file) => [
                        'entry' => $file['entry'],
                        'size' => $file['size'],
                        'sha256' => $file['sha256'],
                    ], $manifest['files']),
                ];

                if (! $zip->addFromString('manifest.json', json_encode($publicManifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR))) {
                    throw new RuntimeException('Could not write the backup manifest.');
                }
            } finally {
                $zip->close();
            }

            if (! is_file($archivePath) || filesize($archivePath) < 100) {
                throw new RuntimeException('The backup archive was not created correctly.');
            }

            @chmod($archivePath, 0600);

            return $this->describeBackup($archivePath);
        } catch (Throwable $exception) {
            if (is_file($archivePath)) {
                @unlink($archivePath);
            }

            throw $exception;
        } finally {
            File::deleteDirectory($workDirectory);
        }
    }

    /** Return newest-first metadata for generated backup archives. */
    public function all(): array
    {
        $this->ensureBackupDirectory();
        $paths = glob($this->backupRoot().DIRECTORY_SEPARATOR.self::ARCHIVE_PREFIX.'*.zip') ?: [];
        rsort($paths, SORT_STRING);

        return array_values(array_filter(array_map(function (string $path) {
            try {
                return $this->describeBackup($path);
            } catch (Throwable $exception) {
                Log::warning('Unable to read backup archive metadata.', [
                    'file' => basename($path),
                    'error' => $exception->getMessage(),
                ]);

                return [
                    'filename' => basename($path),
                    'created_at' => date(DATE_ATOM, (int) @filemtime($path)),
                    'type' => 'unknown',
                    'size' => (int) @filesize($path),
                    'file_count' => null,
                    'database' => null,
                    'status' => 'Metadata unavailable',
                ];
            }
        }, $paths)));
    }

    public function getBackupPath(string $filename): string
    {
        $this->assertSafeFilename($filename);
        $path = $this->backupRoot().DIRECTORY_SEPARATOR.$filename;

        abort_unless(is_file($path), 404, 'Backup file not found.');

        return $path;
    }

    public function delete(string $filename): void
    {
        $path = $this->getBackupPath($filename);
        if (! @unlink($path)) {
            throw new RuntimeException('Could not delete the selected backup file.');
        }
    }

    /** Restore a previously stored archive or an uploaded ZIP file. */
    public function restore(string $archivePath): array
    {
        $this->ensureZipExtension();
        $this->ensureBackupDirectory();

        if (! is_file($archivePath)) {
            throw new RuntimeException('The selected backup archive could not be found.');
        }

        $zip = new ZipArchive();
        $openResult = $zip->open($archivePath);
        if ($openResult !== true) {
            throw new RuntimeException('The selected file is not a valid ZIP archive.');
        }

        $stageDirectory = $this->backupRoot().DIRECTORY_SEPARATOR.'.restore_'.Str::uuid();
        File::ensureDirectoryExists($stageDirectory);
        $sqlPath = $stageDirectory.DIRECTORY_SEPARATOR.'database.sql';
        $databaseRestored = false;

        try {
            $manifestContent = $zip->getFromName('manifest.json');
            if (! is_string($manifestContent)) {
                throw new RuntimeException('This ZIP does not contain a Sarana backup manifest.');
            }

            try {
                $manifest = json_decode($manifestContent, true, 512, JSON_THROW_ON_ERROR);
            } catch (Throwable) {
                throw new RuntimeException('The backup manifest is damaged or unreadable.');
            }

            $this->validateManifest($manifest, $zip);
            $this->extractAndVerifyDatabase($zip, $manifest, $sqlPath);
            $this->stageAndVerifyFiles($zip, $manifest, $stageDirectory);

            // Preserve the currently running application state before any destructive import.
            $safetyBackup = $this->create('pre_restore');

            $this->importDatabase($sqlPath);
            $databaseRestored = true;

            $this->replaceManagedDirectories($stageDirectory);

            return [
                'safety_backup' => $safetyBackup['filename'],
                'restored_database' => (string) ($manifest['database']['name'] ?? 'database'),
                'restored_files' => count($manifest['files']),
                'backup_created_at' => (string) $manifest['created_at'],
            ];
        } catch (Throwable $exception) {
            if ($databaseRestored) {
                Log::critical('A backup database import completed but application file restoration failed.', [
                    'archive' => basename($archivePath),
                    'error' => $exception->getMessage(),
                ]);
                throw new RuntimeException(
                    'The database was restored, but the uploaded files could not all be put back. A pre-restore safety backup was created; restore it to return to the previous state. Details: '.$exception->getMessage(),
                    previous: $exception
                );
            }

            throw $exception;
        } finally {
            $zip->close();
            File::deleteDirectory($stageDirectory);
        }
    }

    /** Remove archives older than the configured retention period. */
    public function prune(): int
    {
        $retentionDays = max(0, (int) config('backup.retention_days', 30));
        if ($retentionDays === 0) {
            return 0;
        }

        $cutoff = now()->subDays($retentionDays)->getTimestamp();
        $deleted = 0;

        foreach (glob($this->backupRoot().DIRECTORY_SEPARATOR.self::ARCHIVE_PREFIX.'*.zip') ?: [] as $path) {
            if (is_file($path) && (int) filemtime($path) < $cutoff && @unlink($path)) {
                $deleted++;
            }
        }

        return $deleted;
    }

    private function databaseConnection(): array
    {
        $connection = DB::connection();
        $config = $connection->getConfig();
        $driver = strtolower((string) ($config['driver'] ?? ''));

        if (! in_array($driver, ['mysql', 'mariadb'], true)) {
            throw new RuntimeException('Automated backups currently support MySQL and MariaDB. The configured database driver is "'.$driver.'".');
        }

        $database = (string) $connection->getDatabaseName();
        if ($database === '') {
            throw new RuntimeException('No database name is configured. Check DB_DATABASE in your .env file.');
        }

        return [
            'driver' => $driver,
            'database' => $database,
            'host' => (string) ($config['host'] ?? '127.0.0.1'),
            'port' => (string) ($config['port'] ?? '3306'),
            'username' => (string) ($config['username'] ?? ''),
            'password' => (string) ($config['password'] ?? ''),
            'socket' => (string) ($config['unix_socket'] ?? ''),
        ];
    }

    private function dumpDatabase(array $connection, string $sqlPath): void
    {
        $binary = $this->resolveDatabaseBinary('mysql_dump_path', 'mysqldump');
        $command = [
            $binary,
            '--default-character-set=utf8mb4',
            '--single-transaction',
            '--quick',
            '--skip-lock-tables',
            '--routines',
            '--triggers',
            '--events',
            '--hex-blob',
            '--result-file='.$sqlPath,
        ];

        $this->appendConnectionArguments($command, $connection);
        $command[] = $connection['database'];

        $process = new Process($command, base_path(), $this->databaseClientEnvironment($connection['password']));
        $process->setTimeout(1800);
        $process->run();

        if (! $process->isSuccessful()) {
            $detail = trim($process->getErrorOutput());
            $hint = str_contains($detail, '10106')
                ? ' Windows could not initialize its network provider for mysqldump. The backup process now inherits the Windows system environment; if this error also occurs when running mysqldump directly from CMD, repair Winsock with an elevated Command Prompt using: netsh winsock reset, then restart Windows.'
                : '';

            throw new RuntimeException('Database backup failed. Check the MySQL/MariaDB tools and database permissions.'.($detail !== '' ? ' '.$detail : '').$hint);
        }

        if (! is_file($sqlPath) || filesize($sqlPath) < 1) {
            throw new RuntimeException('MySQL did not create a database dump.');
        }
    }

    private function importDatabase(string $sqlPath): void
    {
        $connection = $this->databaseConnection();
        $binary = $this->resolveDatabaseBinary('mysql_path', 'mysql');
        $command = [$binary, '--default-character-set=utf8mb4'];
        $this->appendConnectionArguments($command, $connection);
        $command[] = $connection['database'];

        $input = fopen($sqlPath, 'rb');
        if ($input === false) {
            throw new RuntimeException('Could not read the verified SQL dump.');
        }

        try {
            $process = new Process($command, base_path(), $this->databaseClientEnvironment($connection['password']), $input);
            $process->setTimeout(3600);
            $process->run();

            if (! $process->isSuccessful()) {
                $detail = trim($process->getErrorOutput());
                throw new RuntimeException('The database restore failed. The database may be partially restored; use the automatically-created pre-restore backup if needed.'.($detail !== '' ? ' '.$detail : ''));
            }
        } finally {
            fclose($input);
        }
    }

    /**
     * Build a safe environment for MySQL command-line tools.
     *
     * On Windows, child processes launched from Apache/PHP may not receive
     * SYSTEMROOT/WINDIR consistently. MySQL needs the Windows networking
     * provider DLLs to initialize Winsock, otherwise it can fail with
     * CR_IPSOCK_ERROR / WSAEPROVIDERFAILEDINIT (10106).
     */
    private function databaseClientEnvironment(string $password): array
    {
        $environment = ['MYSQL_PWD' => $password];

        if (PHP_OS_FAMILY !== 'Windows') {
            return $environment;
        }

        foreach (['SYSTEMROOT', 'WINDIR', 'PATH', 'TEMP', 'TMP', 'COMSPEC'] as $name) {
            $value = $this->readEnvironmentVariable($name);
            if ($value !== null) {
                $environment[$name] = $value;
            }
        }

        // If Apache did not expose SYSTEMROOT, detect common Windows locations.
        if (empty($environment['SYSTEMROOT'])) {
            foreach (['C:\Windows', 'D:\Windows', 'C:\WinNT', 'D:\WinNT'] as $candidate) {
                if (is_dir($candidate)) {
                    $environment['SYSTEMROOT'] = $candidate;
                    break;
                }
            }
        }

        if (! empty($environment['SYSTEMROOT']) && empty($environment['WINDIR'])) {
            $environment['WINDIR'] = $environment['SYSTEMROOT'];
        }

        return $environment;
    }

    /** Read a Windows environment variable case-insensitively from PHP/getenv. */
    private function readEnvironmentVariable(string $name): ?string
    {
        $value = getenv($name);
        if ($value !== false && $value !== '') {
            return (string) $value;
        }

        foreach ([$_SERVER ?? [], $_ENV ?? []] as $source) {
            foreach ($source as $key => $candidate) {
                if (strcasecmp((string) $key, $name) === 0 && is_scalar($candidate) && (string) $candidate !== '') {
                    return (string) $candidate;
                }
            }
        }

        return null;
    }

    private function appendConnectionArguments(array &$command, array $connection): void
    {
        if ($connection['socket'] !== '') {
            $command[] = '--socket='.$connection['socket'];
        } else {
            $command[] = '--host='.$connection['host'];
            $command[] = '--port='.$connection['port'];
        }

        if ($connection['username'] !== '') {
            $command[] = '--user='.$connection['username'];
        }
    }

    private function resolveDatabaseBinary(string $configKey, string $name): string
    {
        $configured = trim((string) config('backup.'.$configKey, ''));
        if ($configured !== '') {
            if (is_file($configured)) {
                return $configured;
            }

            $resolved = (new ExecutableFinder())->find($configured);
            if ($resolved !== null) {
                return $resolved;
            }

            throw new RuntimeException('The configured '.$name.' path does not exist. Check BACKUP_'.strtoupper($configKey === 'mysql_dump_path' ? 'MYSQLDUMP' : 'MYSQL').'_PATH in .env.');
        }

        $alternatives = $name === 'mysqldump' ? ['mysqldump', 'mariadb-dump'] : ['mysql', 'mariadb'];
        $candidates = [];
        if (PHP_OS_FAMILY === 'Windows') {
            foreach (['D:\Xamm', 'D:\XAMPP', 'D:\xampp', 'C:\xampp', 'C:\Xampp'] as $xampp) {
                foreach ($alternatives as $alternative) {
                    $candidates[] = $xampp.'\\mysql\\bin\\'.$alternative.'.exe';
                }
            }
        } else {
            foreach ($alternatives as $alternative) {
                $candidates[] = '/usr/bin/'.$alternative;
                $candidates[] = '/usr/local/bin/'.$alternative;
                $candidates[] = '/opt/homebrew/bin/'.$alternative;
            }
        }

        foreach ($candidates as $candidate) {
            if (is_file($candidate)) {
                return $candidate;
            }
        }

        $finder = new ExecutableFinder();
        foreach ($alternatives as $alternative) {
            $resolved = $finder->find($alternative);
            if ($resolved !== null) {
                return $resolved;
            }
        }

        throw new RuntimeException('Could not find '.$name.'. Install MySQL/MariaDB client tools or set BACKUP_'.strtoupper($configKey === 'mysql_dump_path' ? 'MYSQLDUMP' : 'MYSQL').'_PATH in your .env file.');
    }

    /** @return array<int, array{entry:string,size:int,sha256:string,source:string}> */
    private function collectApplicationFiles(): array
    {
        $files = [];
        foreach (self::MANAGED_DIRECTORIES as $archiveDirectory => $sourceDirectory) {
            $absoluteDirectory = base_path($sourceDirectory);
            if (! is_dir($absoluteDirectory) || is_link($absoluteDirectory)) {
                continue;
            }

            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($absoluteDirectory, FilesystemIterator::SKIP_DOTS)
            );

            foreach ($iterator as $fileInfo) {
                if (! $fileInfo->isFile() || $fileInfo->isLink()) {
                    continue;
                }

                $source = $fileInfo->getPathname();
                $relative = ltrim(str_replace('\\', '/', substr($source, strlen($absoluteDirectory))), '/');
                if ($relative === '' || $this->hasUnsafePathSegment($relative)) {
                    continue;
                }

                $files[] = [
                    'entry' => 'files/'.$archiveDirectory.'/'.$relative,
                    'size' => (int) $fileInfo->getSize(),
                    'sha256' => $this->sha256File($source),
                    'source' => $source,
                ];
            }
        }

        return $files;
    }

    private function validateManifest(mixed $manifest, ZipArchive $zip): void
    {
        if (! is_array($manifest)
            || ($manifest['format'] ?? null) !== 'sarana-coffee-backup'
            || (int) ($manifest['format_version'] ?? 0) !== 1
            || ! is_array($manifest['database_dump'] ?? null)
            || ! is_array($manifest['files'] ?? null)) {
            throw new RuntimeException('This backup format is not supported or the manifest is incomplete.');
        }

        $databaseDump = $manifest['database_dump'];
        $databaseIndex = $zip->locateName('database.sql');
        $databaseStat = $databaseIndex === false ? false : $zip->statIndex($databaseIndex);
        if (($databaseDump['entry'] ?? null) !== 'database.sql'
            || ! is_int($databaseDump['size'] ?? null)
            || $databaseDump['size'] < 1
            || ! $this->isSha256($databaseDump['sha256'] ?? null)
            || ! is_array($databaseStat)
            || (int) ($databaseStat['size'] ?? -1) !== $databaseDump['size']) {
            throw new RuntimeException('The database dump is missing or has invalid metadata.');
        }

        $maxFiles = max(1, (int) config('backup.max_files', 20000));
        if (count($manifest['files']) > $maxFiles) {
            throw new RuntimeException('This backup contains too many files to restore safely.');
        }

        $totalBytes = $databaseDump['size'];
        $seenEntries = [];
        foreach ($manifest['files'] as $file) {
            if (! is_array($file)
                || ! is_string($file['entry'] ?? null)
                || ! is_int($file['size'] ?? null)
                || $file['size'] < 0
                || ! $this->isSha256($file['sha256'] ?? null)) {
                throw new RuntimeException('A file entry in the backup manifest is invalid.');
            }

            $entry = $file['entry'];
            if (! $this->isAllowedArchiveFile($entry) || $this->hasUnsafePathSegment(substr($entry, 6))) {
                throw new RuntimeException('The backup contains a file path that is not allowed.');
            }

            if (isset($seenEntries[$entry])) {
                throw new RuntimeException('The backup manifest contains a duplicate file path: '.$entry);
            }
            $seenEntries[$entry] = true;

            $entryIndex = $zip->locateName($entry);
            $entryStat = $entryIndex === false ? false : $zip->statIndex($entryIndex);
            if (! is_array($entryStat) || (int) ($entryStat['size'] ?? -1) !== $file['size']) {
                throw new RuntimeException('A file referenced by the manifest is missing or has an invalid size: '.$entry);
            }

            $totalBytes += $file['size'];
            if ($totalBytes > (int) config('backup.max_uncompressed_bytes', 21474836480)) {
                throw new RuntimeException('This backup is larger than the configured safe restore limit.');
            }
        }
    }

    private function extractAndVerifyDatabase(ZipArchive $zip, array $manifest, string $sqlPath): void
    {
        $metadata = $manifest['database_dump'];
        $stream = $zip->getStream('database.sql');
        if ($stream === false) {
            throw new RuntimeException('Could not read database.sql from the ZIP archive.');
        }

        $hash = hash_init('sha256');
        $written = 0;
        $output = fopen($sqlPath, 'wb');
        if ($output === false) {
            fclose($stream);
            throw new RuntimeException('Could not prepare a temporary SQL file for restore.');
        }

        try {
            while (! feof($stream)) {
                $chunk = fread($stream, 1024 * 1024);
                if ($chunk === false) {
                    throw new RuntimeException('The database dump could not be read completely.');
                }
                if ($chunk === '') {
                    continue;
                }
                $written += strlen($chunk);
                if ($written > $metadata['size']) {
                    throw new RuntimeException('The database dump exceeds its declared size.');
                }
                hash_update($hash, $chunk);
                if (fwrite($output, $chunk) !== strlen($chunk)) {
                    throw new RuntimeException('Could not write the temporary SQL dump to disk.');
                }
            }
        } finally {
            fclose($stream);
            fclose($output);
        }

        if ($written !== $metadata['size'] || ! hash_equals($metadata['sha256'], hash_final($hash))) {
            throw new RuntimeException('The database dump failed its integrity check. Restore was cancelled.');
        }
    }

    private function stageAndVerifyFiles(ZipArchive $zip, array $manifest, string $stageDirectory): void
    {
        foreach (array_keys(self::MANAGED_DIRECTORIES) as $managedDirectory) {
            File::ensureDirectoryExists($stageDirectory.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $managedDirectory));
        }

        foreach ($manifest['files'] as $file) {
            $entry = $file['entry'];
            $relativePath = substr($entry, strlen('files/'));
            $destination = $stageDirectory.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
            File::ensureDirectoryExists(dirname($destination));

            $stream = $zip->getStream($entry);
            if ($stream === false) {
                throw new RuntimeException('Could not read an uploaded application file: '.$entry);
            }

            $output = fopen($destination, 'wb');
            if ($output === false) {
                fclose($stream);
                throw new RuntimeException('Could not stage an uploaded application file: '.$entry);
            }

            $hash = hash_init('sha256');
            $written = 0;
            try {
                while (! feof($stream)) {
                    $chunk = fread($stream, 1024 * 1024);
                    if ($chunk === false) {
                        throw new RuntimeException('Could not read an uploaded file completely: '.$entry);
                    }
                    if ($chunk === '') {
                        continue;
                    }
                    $written += strlen($chunk);
                    if ($written > $file['size']) {
                        throw new RuntimeException('An application file exceeds its declared size: '.$entry);
                    }
                    hash_update($hash, $chunk);
                    if (fwrite($output, $chunk) !== strlen($chunk)) {
                        throw new RuntimeException('Could not stage an uploaded file: '.$entry);
                    }
                }
            } finally {
                fclose($stream);
                fclose($output);
            }

            if ($written !== $file['size'] || ! hash_equals($file['sha256'], hash_final($hash))) {
                throw new RuntimeException('An application file failed its integrity check: '.$entry);
            }
        }
    }

    /** Swap the application data folders only after the database import succeeds. */
    private function replaceManagedDirectories(string $stageDirectory): void
    {
        $swapId = (string) Str::uuid();
        $swaps = [];

        try {
            foreach (self::MANAGED_DIRECTORIES as $relativeDirectory => $sourceDirectory) {
                $staged = $stageDirectory.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $relativeDirectory);
                $target = base_path($sourceDirectory);
                $old = $target.'.pre_restore_'.$swapId;
                $hadOriginal = file_exists($target) || is_link($target);

                if (is_link($target)) {
                    throw new RuntimeException('A managed upload directory is a symbolic link and cannot be safely replaced: '.$sourceDirectory);
                }

                if ($hadOriginal && ! @rename($target, $old)) {
                    throw new RuntimeException('Could not move the current upload directory out of the way: '.$sourceDirectory);
                }

                $swaps[] = ['target' => $target, 'old' => $old, 'had_original' => $hadOriginal, 'installed' => false];
                if (! @rename($staged, $target)) {
                    throw new RuntimeException('Could not install restored files into: '.$sourceDirectory);
                }
                $swaps[count($swaps) - 1]['installed'] = true;
            }
        } catch (Throwable $exception) {
            foreach (array_reverse($swaps) as $swap) {
                if ($swap['installed'] && (is_dir($swap['target']) || is_file($swap['target']))) {
                    File::deleteDirectory($swap['target']);
                    if (is_file($swap['target'])) {
                        @unlink($swap['target']);
                    }
                }
                if ($swap['had_original'] && (is_dir($swap['old']) || is_file($swap['old']))) {
                    @rename($swap['old'], $swap['target']);
                }
            }

            throw $exception;
        }

        foreach ($swaps as $swap) {
            if ($swap['had_original']) {
                File::deleteDirectory($swap['old']);
                if (is_file($swap['old'])) {
                    @unlink($swap['old']);
                }
            }
        }
    }

    private function describeBackup(string $path): array
    {
        $createdAt = date(DATE_ATOM, (int) filemtime($path));
        $type = 'unknown';
        $fileCount = null;
        $database = null;
        $status = 'Archive found';

        $zip = new ZipArchive();
        if ($zip->open($path) === true) {
            $content = $zip->getFromName('manifest.json');
            if (is_string($content)) {
                $manifest = json_decode($content, true);
                if (is_array($manifest) && ($manifest['format'] ?? null) === 'sarana-coffee-backup') {
                    $createdAt = (string) ($manifest['created_at'] ?? $createdAt);
                    $type = (string) ($manifest['type'] ?? $type);
                    $fileCount = count($manifest['files'] ?? []);
                    $database = (string) ($manifest['database']['name'] ?? '');
                    $status = 'Ready';
                }
            }
            $zip->close();
        }

        return [
            'filename' => basename($path),
            'created_at' => $createdAt,
            'type' => $type,
            'size' => (int) filesize($path),
            'file_count' => $fileCount,
            'database' => $database,
            'status' => $status,
        ];
    }

    private function safeType(string $type): string
    {
        $type = Str::of($type)->lower()->replaceMatches('/[^a-z0-9_-]/', '_')->substr(0, 24)->toString();

        return $type !== '' ? $type : 'manual';
    }

    private function assertSafeFilename(string $filename): void
    {
        abort_unless(
            preg_match('/\Acoffee_backup_[A-Za-z0-9._-]+\.zip\z/', $filename) === 1,
            404,
            'Backup file not found.'
        );
    }

    private function backupRoot(): string
    {
        $relative = trim((string) config('backup.directory', 'backups'), '/\\');
        if ($relative === '' || $this->hasUnsafePathSegment(str_replace('\\', '/', $relative))) {
            throw new RuntimeException('BACKUP_DIRECTORY must be a relative folder inside storage/app/private.');
        }

        return storage_path('app/private/'.str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $relative));
    }

    private function ensureBackupDirectory(): void
    {
        File::ensureDirectoryExists($this->backupRoot());
        @chmod($this->backupRoot(), 0700);
    }

    private function ensureZipExtension(): void
    {
        if (! class_exists(ZipArchive::class)) {
            throw new RuntimeException('The PHP ZIP extension is not enabled. Enable extension=zip in php.ini, then restart PHP/Apache.');
        }
    }

    private function isAllowedArchiveFile(string $entry): bool
    {
        foreach (array_keys(self::MANAGED_DIRECTORIES) as $directory) {
            if (str_starts_with($entry, 'files/'.$directory.'/')) {
                return true;
            }
        }

        return false;
    }

    private function hasUnsafePathSegment(string $path): bool
    {
        if ($path === '' || preg_match('/[\x00-\x1F<>:"|?*]/', $path) === 1 || str_contains($path, '\\') || str_starts_with($path, '/')) {
            return true;
        }

        foreach (explode('/', $path) as $segment) {
            if ($segment === '' || $segment === '.' || $segment === '..') {
                return true;
            }
        }

        return false;
    }

    private function sha256File(string $path): string
    {
        $hash = hash_file('sha256', $path);
        if (! is_string($hash)) {
            throw new RuntimeException('Could not calculate a SHA-256 checksum for: '.basename($path));
        }

        return $hash;
    }

    private function isSha256(mixed $value): bool
    {
        return is_string($value) && preg_match('/\A[a-f0-9]{64}\z/i', $value) === 1;
    }
}
