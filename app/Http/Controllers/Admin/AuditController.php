<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use App\Support\DataTableResponse;
use Illuminate\Http\Request;

class AuditController extends Controller
{
    /** Audit trail page; the table is filled by the DataTables request. */
    public function index(Request $request)
    {
        if ($request->has('draw')) {
            return $this->rows($request);
        }

        return view('admin.audit.index', [
            'users' => User::orderBy('last_name')->orderBy('first_name')->get(),
            'actions' => ['auth', 'incoming', 'outgoing', 'duty', 'attachment', 'user', 'backup', 'report'],
        ]);
    }

    private function rows(Request $request)
    {
        $query = AuditLog::with('user')
            ->when($request->input('action'), fn ($q, $a) => $q->where('action', 'like', $a.'%'))
            ->when($request->input('user_id'), fn ($q, $u) => $q->where('user_id', $u))
            ->when($request->input('from'), fn ($q, $d) => $q->whereDate('created_at', '>=', $d))
            ->when($request->input('to'), fn ($q, $d) => $q->whereDate('created_at', '<=', $d));

        return DataTableResponse::make(
            $request,
            $query,
            [0 => 'created_at', 2 => 'action', 3 => 'reference_no', 5 => 'ip_address'],
            fn ($q, $t) => $q->where(fn ($w) => $w->where('reference_no', 'like', "%{$t}%")
                ->orWhere('description', 'like', "%{$t}%")
                ->orWhere('reason', 'like', "%{$t}%")
                ->orWhere('action', 'like', "%{$t}%")),
            function (AuditLog $log) {
                $view = view('admin.audit._row', ['log' => $log]);

                return ['cells' => array_map(fn ($c) => $view->fragment($c), ['time', 'user', 'action', 'reference', 'details', 'ip'])];
            },
        );
    }
}
