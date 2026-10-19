<x-layouts.app title="Backup & Restore">
    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-xl font-semibold text-navy-900">Backup &amp; restore</h1>
            <p class="text-sm text-slate-500">Each backup contains the database <em>and</em> all uploaded attachments in a single archive.</p>
        </div>
        <form method="POST" action="{{ route('admin.backups.store') }}">@csrf<button class="btn btn-primary"><x-icon name="backup" /> Create backup now</button></form>
    </div>

    <div class="mb-6 grid gap-4 md:grid-cols-3">
        <div class="card p-5">
            <div class="text-[11px] font-semibold tracking-wider text-navy-500 uppercase">Backup folder</div>
            <div class="mt-1 font-mono text-xs break-all text-navy-900">{{ $path }}</div>
            <p class="hint">Set <code>DTS_BACKUP_PATH</code> in <code>.env</code> to the approved external drive.</p>
        </div>
        <div class="card p-5">
            <div class="text-[11px] font-semibold tracking-wider text-navy-500 uppercase">Latest backup</div>
            <div class="mt-1 text-sm font-semibold text-navy-900">{{ $files ? date('d M Y H:i', $files[0]['modified']).'H' : 'None yet' }}</div>
        </div>
        <div class="card p-5">
            <div class="text-[11px] font-semibold tracking-wider text-navy-500 uppercase">Last successful restore test</div>
            <div class="mt-1 text-sm font-semibold {{ $lastTest ? 'text-emerald-700' : 'text-amber-700' }}">{{ $lastTest ? $lastTest->created_at->format('d M Y H:i').'H' : 'Never tested' }}</div>
        </div>
    </div>

    <div class="grid gap-6 xl:grid-cols-2">
        <div class="card">
            <div class="card-header"><h2 class="card-title">Backup archives</h2></div>
            <ul class="divide-y divide-navy-50">
                @forelse ($files as $f)
                    <li class="flex flex-wrap items-center gap-3 px-5 py-3">
                        <div class="min-w-0 flex-1">
                            <div class="font-mono text-xs text-navy-900">{{ $f['name'] }}</div>
                            <div class="text-[11px] text-slate-500">{{ date('d M Y H:i', $f['modified']) }} · {{ number_format($f['size'] / 1024, 1) }} KB</div>
                        </div>
                        <form method="POST" action="{{ route('admin.backups.restore-test') }}">@csrf<input type="hidden" name="file" value="{{ $f['name'] }}"><button class="btn btn-secondary btn-sm">Test restore</button></form>
                        <a href="{{ route('admin.backups.download', ['file' => $f['name']]) }}" class="btn btn-secondary btn-sm">Download</a>
                    </li>
                @empty
                    <li class="px-5 py-6 text-center text-sm text-slate-400">No backups yet.</li>
                @endforelse
            </ul>
        </div>

        <div class="card">
            <div class="card-header"><h2 class="card-title">Backup &amp; restore-test log</h2></div>
            <ul class="divide-y divide-navy-50">
                @forelse ($logs as $log)
                    <li class="px-5 py-3">
                        <div class="flex items-center justify-between gap-2">
                            <span class="text-sm font-medium text-navy-900">{{ $log->type === 'backup' ? 'Backup' : 'Restore test' }}</span>
                            <span class="badge {{ $log->status === 'success' ? 'bg-emerald-50 text-emerald-700' : 'bg-red-50 text-red-700' }}">{{ ucfirst($log->status) }}</span>
                        </div>
                        <div class="text-[11px] text-slate-500">{{ $log->created_at->format('d M Y H:i') }} · {{ $log->user?->displayName() ?? 'Scheduler' }}{{ $log->file_path ? ' · '.basename($log->file_path) : '' }}</div>
                        <div class="mt-0.5 text-xs break-words text-slate-600">{{ $log->details }}</div>
                    </li>
                @empty
                    <li class="px-5 py-6 text-center text-sm text-slate-400">No entries.</li>
                @endforelse
            </ul>
        </div>
    </div>

    <div class="card mt-6 p-5 text-sm text-slate-700">
        <h2 class="card-title mb-2">Procedure</h2>
        <ol class="list-decimal space-y-1 pl-5">
            <li>Create a backup at the end of each duty day (or let the scheduler create one at 18:00H).</li>
            <li>Copy the archive to the approved external storage and keep it according to office policy.</li>
            <li>Run <strong>Test restore</strong> at least monthly. It restores the archive into a separate scratch database, compares every table and attachment count with the backup manifest, and then deletes the scratch database. The live database is never touched.</li>
            <li>For a real restoration, follow the steps in <code>docs/BACKUP-AND-RESTORE.md</code>.</li>
        </ol>
    </div>
</x-layouts.app>
