<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;

class AuditController extends Controller
{
    public function index(Request $request)
    {
        $logs = AuditLog::with('user')
            ->when($request->input('q'), fn ($q, $t) => $q->where(fn ($w) => $w->where('reference_no', 'like', "%{$t}%")->orWhere('description', 'like', "%{$t}%")->orWhere('reason', 'like', "%{$t}%")))
            ->when($request->input('action'), fn ($q, $a) => $q->where('action', 'like', $a.'%'))
            ->when($request->input('user_id'), fn ($q, $u) => $q->where('user_id', $u))
            ->when($request->input('from'), fn ($q, $d) => $q->whereDate('created_at', '>=', $d))
            ->when($request->input('to'), fn ($q, $d) => $q->whereDate('created_at', '<=', $d))
            ->latest('id')
            ->paginate(50)->withQueryString();

        return view('admin.audit.index', [
            'logs' => $logs,
            'users' => User::orderBy('name')->get(),
            'actions' => ['auth', 'incoming', 'outgoing', 'duty', 'attachment', 'user', 'backup', 'report'],
        ]);
    }
}
