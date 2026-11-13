<?php

namespace App\Http\Controllers;

use App\Services\BackupManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

class BackupController extends Controller
{
    public function index(BackupManager $backups)
    {
        $backupFiles = $backups->all();
        $totalBytes = array_sum(array_column($backupFiles, 'size'));
        $latestBackup = $backupFiles[0] ?? null;

        return view('admin.backups.index', compact('backupFiles', 'totalBytes', 'latestBackup'));
    }

    public function store(BackupManager $backups): RedirectResponse
    {
        try {
            $backup = $backups->create('manual');

            return redirect()->route('admin.backups.index')->with(
                'success',
                'Backup created successfully: '.$backup['filename'].' ('.$this->humanSize($backup['size']).').'
            );
        } catch (Throwable $exception) {
            Log::error('Manual backup failed.', ['error' => $exception->getMessage()]);

            return redirect()->route('admin.backups.index')->with('error', $exception->getMessage());
        }
    }

    public function download(string $filename, BackupManager $backups): BinaryFileResponse
    {
        return response()->download($backups->getBackupPath($filename), $filename, [
            'Content-Type' => 'application/zip',
            'Cache-Control' => 'private, no-store',
        ]);
    }

    public function restoreStored(Request $request, string $filename, BackupManager $backups): RedirectResponse
    {
        $request->validate([
            'restore_confirmation' => ['required', Rule::in(['RESTORE'])],
        ], [
            'restore_confirmation.in' => 'Type RESTORE exactly to confirm this destructive action.',
        ]);

        try {
            $result = $backups->restore($backups->getBackupPath($filename));
            Log::warning('Administrator restored a backup archive.', [
                'user_id' => $request->user()?->getAuthIdentifier(),
                'archive' => $filename,
                'safety_backup' => $result['safety_backup'],
            ]);

            return redirect()->route('admin.backups.index')->with(
                'success',
                'Restore completed. A safety backup of the previous data was saved as '.$result['safety_backup'].'.'
            );
        } catch (Throwable $exception) {
            Log::error('Backup restore failed.', [
                'user_id' => $request->user()?->getAuthIdentifier(),
                'archive' => $filename,
                'error' => $exception->getMessage(),
            ]);

            return redirect()->route('admin.backups.index')->with('error', $exception->getMessage());
        }
    }

    public function restoreUploaded(Request $request, BackupManager $backups): RedirectResponse
    {
        $request->validate([
            'backup_file' => ['required', 'file', 'mimes:zip', 'max:'.max(1, (int) config('backup.upload_max_kb', 1024000))],
            'restore_confirmation' => ['required', Rule::in(['RESTORE'])],
        ], [
            'restore_confirmation.in' => 'Type RESTORE exactly to confirm this destructive action.',
        ]);

        $upload = $request->file('backup_file');
        try {
            $result = $backups->restore($upload->getRealPath());
            Log::warning('Administrator restored an uploaded backup archive.', [
                'user_id' => $request->user()?->getAuthIdentifier(),
                'original_filename' => $upload->getClientOriginalName(),
                'safety_backup' => $result['safety_backup'],
            ]);

            return redirect()->route('admin.backups.index')->with(
                'success',
                'Uploaded backup restored successfully. A safety backup of the previous data was saved as '.$result['safety_backup'].'.'
            );
        } catch (Throwable $exception) {
            Log::error('Uploaded backup restore failed.', [
                'user_id' => $request->user()?->getAuthIdentifier(),
                'original_filename' => $upload->getClientOriginalName(),
                'error' => $exception->getMessage(),
            ]);

            return redirect()->route('admin.backups.index')->with('error', $exception->getMessage());
        }
    }

    public function destroy(string $filename, BackupManager $backups): RedirectResponse
    {
        try {
            $backups->delete($filename);

            return redirect()->route('admin.backups.index')->with('success', 'Backup deleted: '.$filename);
        } catch (Throwable $exception) {
            return redirect()->route('admin.backups.index')->with('error', $exception->getMessage());
        }
    }

    private function humanSize(int $bytes): string
    {
        if ($bytes >= 1024 ** 3) {
            return number_format($bytes / (1024 ** 3), 2).' GB';
        }
        if ($bytes >= 1024 ** 2) {
            return number_format($bytes / (1024 ** 2), 2).' MB';
        }

        return number_format($bytes / 1024, 1).' KB';
    }
}
