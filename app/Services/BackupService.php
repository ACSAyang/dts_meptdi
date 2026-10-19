<?php

namespace App\Services;

use App\Models\BackupLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use RuntimeException;
use ZipArchive;

/**
 * Backs up the database AND uploaded attachments into one zip archive,
 * and tests that an archive can actually be restored.
 */
class BackupService
{
    /** Tables whose row counts are recorded in the manifest and checked on restore. */
    private const TABLES = [
        'users', 'duty_periods', 'duty_turnovers', 'incoming_communications', 'outgoing_communications',
        'delivery_attempts', 'record_events', 'attachments', 'audit_logs', 'reference_sequences',
    ];

    public function backupPath(): string
    {
        $path = rtrim(config('dts.backup.path'), '/\\');
        File::ensureDirectoryExists($path);

        return $path;
    }

    /** @return array<int, array{name: string, path: string, size: int, modified: int}> */
    public function list(): array
    {
        return collect(File::glob($this->backupPath().'/dts-backup-*.zip'))
            ->map(fn ($f) => ['name' => basename($f), 'path' => $f, 'size' => filesize($f), 'modified' => filemtime($f)])
            ->sortByDesc('modified')->values()->all();
    }

    public function run(?User $user = null): BackupLog
    {
        $stamp = now()->format('Ymd-His');
        $zipPath = $this->backupPath()."/dts-backup-{$stamp}.zip";
        $sqlPath = storage_path("app/dts-dump-{$stamp}.sql");

        try {
            $this->dumpDatabase($sqlPath);

            $zip = new ZipArchive;
            if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                throw new RuntimeException("Cannot create {$zipPath}");
            }
            $zip->addFile($sqlPath, 'database.sql');

            $filesRoot = storage_path('app/private/attachments');
            $fileCount = 0;
            if (File::isDirectory($filesRoot)) {
                foreach (File::allFiles($filesRoot) as $file) {
                    $zip->addFile($file->getPathname(), 'files/attachments/'.str_replace('\\', '/', $file->getRelativePathname()));
                    $fileCount++;
                }
            }

            $manifest = [
                'system' => 'MEPTDI DTS',
                'created_at' => now()->toIso8601String(),
                'database' => config('database.connections.mysql.database'),
                'row_counts' => $this->rowCounts(DB::connection()),
                'attachment_files' => $fileCount,
            ];
            $zip->addFromString('manifest.json', json_encode($manifest, JSON_PRETTY_PRINT));
            $zip->close();

            return BackupLog::create([
                'type' => 'backup', 'status' => 'success', 'file_path' => $zipPath, 'size' => filesize($zipPath),
                'details' => "Database + {$fileCount} attachment file(s).", 'user_id' => $user?->id,
            ]);
        } catch (\Throwable $e) {
            File::delete($zipPath);

            return BackupLog::create(['type' => 'backup', 'status' => 'failed', 'details' => $e->getMessage(), 'user_id' => $user?->id]);
        } finally {
            File::delete($sqlPath);
        }
    }

    /**
     * Restores the archive into a separate scratch database (never the live one),
     * then compares row counts and attachment files against the manifest.
     */
    public function restoreTest(string $zipPath, ?User $user = null): BackupLog
    {
        $testDb = config('dts.backup.restore_test_database');
        $work = storage_path('app/restore-test-'.now()->format('YmdHis'));

        try {
            if (! is_file($zipPath)) {
                throw new RuntimeException('Backup file not found.');
            }

            $zip = new ZipArchive;
            if ($zip->open($zipPath) !== true) {
                throw new RuntimeException('Archive cannot be opened (corrupted?).');
            }
            $zip->extractTo($work);
            $zip->close();

            $manifest = json_decode((string) @file_get_contents("{$work}/manifest.json"), true);
            if (! $manifest || ! is_file("{$work}/database.sql")) {
                throw new RuntimeException('Archive is missing manifest.json or database.sql.');
            }

            DB::statement("DROP DATABASE IF EXISTS `{$testDb}`");
            DB::statement("CREATE DATABASE `{$testDb}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

            $this->mysql(['--database='.$testDb], "{$work}/database.sql");

            config(['database.connections.restore_test' => array_merge(config('database.connections.mysql'), ['database' => $testDb])]);
            DB::purge('restore_test');
            $restored = $this->rowCounts(DB::connection('restore_test'));
            DB::disconnect('restore_test');

            $problems = [];
            foreach ($manifest['row_counts'] as $table => $count) {
                if (($restored[$table] ?? null) !== $count) {
                    $problems[] = "{$table}: expected {$count}, restored ".($restored[$table] ?? 'missing');
                }
            }
            $files = File::isDirectory("{$work}/files") ? count(File::allFiles("{$work}/files")) : 0;
            if ($files !== $manifest['attachment_files']) {
                $problems[] = "attachments: expected {$manifest['attachment_files']} file(s), found {$files}";
            }

            $summary = collect($restored)->map(fn ($c, $t) => "{$t}={$c}")->implode(', ')."; files={$files}";

            return BackupLog::create([
                'type' => 'restore_test',
                'status' => $problems ? 'failed' : 'success',
                'file_path' => $zipPath,
                'details' => $problems ? 'Mismatch: '.implode('; ', $problems) : 'Restored into scratch database and verified. '.$summary,
                'user_id' => $user?->id,
            ]);
        } catch (\Throwable $e) {
            return BackupLog::create(['type' => 'restore_test', 'status' => 'failed', 'file_path' => $zipPath, 'details' => $e->getMessage(), 'user_id' => $user?->id]);
        } finally {
            File::deleteDirectory($work);
            try {
                DB::statement("DROP DATABASE IF EXISTS `{$testDb}`");
            } catch (\Throwable) {
            }
        }
    }

    private function dumpDatabase(string $sqlPath): void
    {
        $c = config('database.connections.mysql');
        $args = [
            config('dts.backup.mysqldump'),
            '--host='.$c['host'], '--port='.$c['port'], '--user='.$c['username'],
            '--single-transaction', '--routines', '--default-character-set=utf8mb4',
            '--result-file='.$sqlPath, $c['database'],
        ];
        if ($c['password'] !== '' && $c['password'] !== null) {
            array_splice($args, 4, 0, ['--password='.$c['password']]);
        }

        $result = Process::timeout(600)->run($args);
        if ($result->failed() || ! is_file($sqlPath) || filesize($sqlPath) === 0) {
            throw new RuntimeException('mysqldump failed: '.trim($result->errorOutput() ?: $result->output()));
        }
    }

    private function mysql(array $extraArgs, string $inputFile): void
    {
        $c = config('database.connections.mysql');
        $args = array_merge([
            config('dts.backup.mysql'),
            '--host='.$c['host'], '--port='.$c['port'], '--user='.$c['username'], '--default-character-set=utf8mb4',
        ], $c['password'] ? ['--password='.$c['password']] : [], $extraArgs);

        $result = Process::timeout(600)->input(fopen($inputFile, 'r'))->run($args);
        if ($result->failed()) {
            throw new RuntimeException('Restore import failed: '.trim($result->errorOutput()));
        }
    }

    /** @return array<string, int> */
    private function rowCounts($connection): array
    {
        return collect(self::TABLES)->mapWithKeys(fn ($t) => [$t => (int) $connection->table($t)->count()])->all();
    }
}
