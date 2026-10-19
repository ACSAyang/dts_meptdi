<?php

namespace App\Http\Controllers;

use App\Models\DutyPeriod;
use App\Models\IncomingCommunication;
use App\Models\OutgoingCommunication;
use App\Services\Audit;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class ReportController extends Controller
{
    public function index()
    {
        return view('reports.index');
    }

    /** Daily register of incoming and outgoing communications (the digital logbook page). */
    public function daily(Request $request)
    {
        $date = Carbon::parse($request->input('date', today()->toDateString()));
        Audit::log('report.daily', null, 'Generated daily register for '.$date->toDateString());

        return view('reports.daily', [
            'date' => $date,
            'incoming' => IncomingCommunication::with('registrar')->whereDate('received_at', $date)->orderBy('received_at')->get(),
            'outgoing' => OutgoingCommunication::with('registrar')->whereDate('created_at', $date)->orderBy('id')->get(),
            'duties' => DutyPeriod::with('user')
                ->whereDate('started_at', '<=', $date)
                ->where(fn ($q) => $q->whereNull('ended_at')->orWhereDate('ended_at', '>=', $date))
                ->orderBy('started_at')->get(),
        ]);
    }

    public function pending(Request $request)
    {
        Audit::log('report.pending', null, 'Generated pending-action report');

        return view('reports.pending', [
            'incoming' => IncomingCommunication::pending()
                ->when($request->boolean('overdue'), fn ($q) => $q->whereNotNull('action_due_at')->where('action_due_at', '<', now()))
                ->orderByRaw('action_due_at IS NULL')->orderBy('action_due_at')->orderBy('received_at')->get(),
        ]);
    }

    public function delivery(Request $request)
    {
        $from = Carbon::parse($request->input('from', today()->startOfMonth()->toDateString()));
        $to = Carbon::parse($request->input('to', today()->toDateString()));
        Audit::log('report.delivery', null, 'Generated delivery report '.$from->toDateString().' to '.$to->toDateString());

        $records = OutgoingCommunication::withCount('deliveryAttempts')
            ->whereDate('created_at', '>=', $from)->whereDate('created_at', '<=', $to)
            ->when($request->input('status'), fn ($q, $s) => $s === 'pending' ? $q->pending() : $q->where('status', $s))
            ->orderBy('id')->get();

        return view('reports.delivery', compact('from', 'to', 'records'));
    }

    public function duty(Request $request)
    {
        $from = Carbon::parse($request->input('from', today()->startOfMonth()->toDateString()));
        $to = Carbon::parse($request->input('to', today()->toDateString()));

        return view('reports.duty', [
            'from' => $from,
            'to' => $to,
            'periods' => DutyPeriod::with(['user', 'turnoverGiven.toUser'])
                ->withCount(['incoming', 'outgoing', 'events'])
                ->whereIn('status', [DutyPeriod::ACTIVE, DutyPeriod::COMPLETED])
                ->whereDate('started_at', '>=', $from)->whereDate('started_at', '<=', $to)
                ->orderBy('started_at')->get(),
        ]);
    }
}
