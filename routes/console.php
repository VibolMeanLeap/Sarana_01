<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schedule;
use App\Services\BackupManager;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');


// Daily full database + uploaded-file backup. Configure a system scheduler to
// execute `php artisan schedule:run` every minute.
Schedule::call(function (): void {
    try {
        app(BackupManager::class)->create('scheduled');
    } catch (\Throwable $exception) {
        Log::error('Scheduled backup failed.', ['error' => $exception->getMessage()]);
    }
})->dailyAt((string) config('backup.daily_at', '02:00'))
    ->timezone((string) config('backup.timezone', 'Asia/Phnom_Penh'))
    ->name('sk-coffee-daily-backup')
    ->withoutOverlapping();

// Keep the private backup directory within the configured retention period.
Schedule::call(function (): void {
    try {
        app(BackupManager::class)->prune();
    } catch (\Throwable $exception) {
        Log::error('Backup retention cleanup failed.', ['error' => $exception->getMessage()]);
    }
})->dailyAt('03:15')
    ->timezone((string) config('backup.timezone', 'Asia/Phnom_Penh'))
    ->name('sk-coffee-daily-backup')
    ->withoutOverlapping();