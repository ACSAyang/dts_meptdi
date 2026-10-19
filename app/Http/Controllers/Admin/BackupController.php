<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BackupLog;
use App\Services\Audit;
use App\Services\BackupService;
use Illuminate\Http\Request;

class BackupController extends Controller
{
    public function __construct(private BackupService $backups) {}

    public function index()
    {
        return view('admin.backups.index', [
            'files' => $this->backups->list(),
            'logs' => BackupLog::with('user')->latest('id')->limit(30)->get(),
            'path' => $this->backups->backupPath(),
            'lastTest' => BackupLog::where('type', 'restore_test')->where('status', 'success')->latest('id')->first(),
        ]);
    }

    public function store(Request $request)
    {
        @set_time_limit(600);
        $log = $this->backups->run($request->user());
        Audit::log('backup.created', $log, $log->status === 'success' ? 'Backup created '.basename($log->file_path) : 'Backup failed', reason: $log->status === 'failed' ? $log->details : null);

        return back()->with($log->status === 'success' ? 'success' : 'error',
            $log->status === 'success' ? 'Backup created: '.basename($log->file_path).'. Copy it to the approved external storage.' : 'Backup failed: '.$log->details);
    }

    public function restoreTest(Request $request)
    {
        @set_time_limit(600);
        $file = $this->resolve($request->input('file'));
        $log = $this->backups->restoreTest($file, $request->user());
        Audit::log('backup.restore_test', $log, 'Restore test of '.basename($file).': '.$log->status, reason: $log->details);

        return back()->with($log->status === 'success' ? 'success' : 'error', 'Restore test '.($log->status === 'success' ? 'PASSED' : 'FAILED').': '.$log->details);
    }

    public function download(Request $request)
    {
        $file = $this->resolve($request->input('file'));
        Audit::log('backup.downloaded', null, 'Downloaded '.basename($file));

        return response()->download($file);
    }

    /** Only files inside the backup folder may be used. */
    private function resolve(?string $name): string
    {
        abort_unless($name && preg_match('/^dts-backup-[0-9-]+\.zip$/', $name), 404);
        $path = $this->backups->backupPath().DIRECTORY_SEPARATOR.$name;
        abort_unless(is_file($path), 404);

        return $path;
    }
}
