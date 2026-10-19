<?php

namespace App\Http\Controllers;

use App\Models\DutyPeriod;
use App\Models\DutyTurnover;
use App\Models\IncomingCommunication;
use App\Models\OutgoingCommunication;
use App\Models\RecordEvent;

class DashboardController extends Controller
{
    public function __invoke()
    {
        $today = today();

        $stats = [
            'incoming_today' => IncomingCommunication::whereDate('received_at', $today)->count(),
            'incoming_month' => IncomingCommunication::whereYear('received_at', $today->year)->whereMonth('received_at', $today->month)->count(),
            'outgoing_today' => OutgoingCommunication::whereDate('created_at', $today)->count(),
            'outgoing_month' => OutgoingCommunication::whereYear('created_at', $today->year)->whereMonth('created_at', $today->month)->count(),
            'pending_actions' => IncomingCommunication::pending()->count(),
            'overdue' => IncomingCommunication::pending()->whereNotNull('action_due_at')->where('action_due_at', '<', now())->count(),
            'awaiting_dispatch' => OutgoingCommunication::where('status', 'registered')->count(),
            'in_transit' => OutgoingCommunication::where('status', 'dispatched')->count(),
            'follow_up' => OutgoingCommunication::where('status', 'follow_up')->count(),
            'for_verification' => OutgoingCommunication::where('status', 'received')->count(),
        ];

        return view('dashboard.index', [
            'stats' => $stats,
            'current' => DutyPeriod::current(),
            'awaitingTurnover' => DutyTurnover::awaitingAcknowledgment(),
            'myNextDuty' => auth()->user()->dutyPeriods()->where('status', DutyPeriod::SCHEDULED)->orderBy('scheduled_start')->first(),
            'pendingIncoming' => IncomingCommunication::pending()
                ->orderByRaw('action_due_at IS NULL')->orderBy('action_due_at')->orderBy('received_at')
                ->limit(8)->get(),
            'pendingOutgoing' => OutgoingCommunication::pending()
                ->orderByRaw("FIELD(status, 'follow_up', 'received', 'registered', 'dispatched')")->latest('id')
                ->limit(8)->get(),
            'recent' => RecordEvent::with(['trackable', 'user'])->latest('occurred_at')->latest('id')->limit(10)->get(),
        ]);
    }
}
