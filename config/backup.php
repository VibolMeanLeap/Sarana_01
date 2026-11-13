<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Backup storage and database tools
    |--------------------------------------------------------------------------
    |
    | Backup archives are always stored inside storage/app/private so they are
    | not directly accessible from the public web directory.
    |
    */
    'directory' => env('BACKUP_DIRECTORY', 'backups'),

    // Optional absolute paths. Leave blank to auto-detect PATH/XAMPP locations.
    'mysql_dump_path' => env('BACKUP_MYSQLDUMP_PATH'),
    'mysql_path' => env('BACKUP_MYSQL_PATH'),

    // Daily scheduler settings. The Laravel scheduler must be run by cron or Task Scheduler.
    'daily_at' => env('BACKUP_DAILY_AT', '02:00'),
    'timezone' => env('BACKUP_TIMEZONE', 'Asia/Phnom_Penh'),
    'retention_days' => (int) env('BACKUP_RETENTION_DAYS', 30),

    // Uploaded restore archives are limited to this size (in kilobytes).
    'upload_max_kb' => (int) env('BACKUP_UPLOAD_MAX_KB', 1024000),

    // Defensive limits applied before extracting a user-supplied ZIP archive.
    'max_files' => (int) env('BACKUP_MAX_FILES', 20000),
    'max_uncompressed_bytes' => (int) env('BACKUP_MAX_UNCOMPRESSED_BYTES', 21474836480), // 20 GiB
];
