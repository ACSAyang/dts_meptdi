<?php

use App\Services\BackupService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('dts:backup', function (BackupService $backups) {
    $log = $backups->run();
    $log->status === 'success'
        ? $this->info("Backup created: {$log->file_path}")
        : $this->error("Backup failed: {$log->details}");

    return $log->status === 'success' ? 0 : 1;
})->purpose('Back up the DTS database and uploaded attachments to the configured backup folder');

Artisan::command('dts:restore-test {file? : Backup zip (defaults to the latest)}', function (BackupService $backups) {
    $file = $this->argument('file') ?? ($backups->list()[0]['path'] ?? null);
    if (! $file) {
        $this->error('No backup found.');

        return 1;
    }

    $this->line("Testing restore of {$file} …");
    $log = $backups->restoreTest($file);
    $log->status === 'success' ? $this->info($log->details) : $this->error($log->details);

    return $log->status === 'success' ? 0 : 1;
})->purpose('Restore a backup into a scratch database and verify it against its manifest');

// Daily backup at 18:00 when the Laravel scheduler is running (see README).
Schedule::command('dts:backup')->dailyAt('18:00');
