<?php

namespace App\Http\Controllers;

use App\Models\DutyPeriod;
use App\Models\DutyTurnover;
use App\Models\IncomingCommunication;
use App\Models\OutgoingCommunication;
use App\Models\User;
use App\Services\Audit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class DutyController extends Controller
{
    public function index()
    {
        return view('duty.index', [
            'current' => DutyPeriod::current(),
            'awaitingTurnover' => DutyTurnover::awaitingAcknowledgment(),
            'history' => DutyPeriod::with(['user', 'turnoverGiven.toUser'])
                ->whereIn('status', [DutyPeriod::ACTIVE, DutyPeriod::COMPLETED])
                ->latest('started_at')->limit(1000)->get(),
        ]);
    }

    /**
     * The signed-in user takes the watch. Only one watch can be on duty at a time,
     * and a pending turnover from the previous watch must be acknowledged first.
     */
    public function start(Request $request)
    {
        $user = $request->user();

        $current = DutyPeriod::current();
        if ($current) {
            return back()->with('error', $current->user->displayName().' is still on duty. The current watch must end duty and turn over first.');
        }

        $turnover = DutyTurnover::awaitingAcknowledgment();
        $data = $request->validate([
            'scheduled_end' => ['required', 'date', 'after:now'],
            'acknowledge' => [$turnover ? 'accepted' : 'nullable'],
            'acknowledgment_remarks' => ['nullable', 'string', 'max:2000'],
        ], [
            'scheduled_end.after' => 'The end of duty must be later than now.',
            'acknowledge.accepted' => 'Tick the box to acknowledge the turnover and pending items before starting duty.',
        ]);

        DB::transaction(function () use ($data, $turnover, $request, $user) {
            $duty = DutyPeriod::create([
                'user_id' => $user->id,
                'assigned_by' => $user->id,
                'scheduled_start' => now(),
                'scheduled_end' => $data['scheduled_end'],
                'started_at' => now(),
                'status' => DutyPeriod::ACTIVE,
            ]);

            if ($turnover) {
                $turnover->update([
                    'to_duty_period_id' => $duty->id,
                    'to_user_id' => $user->id,
                    'acknowledged_at' => now(),
                    'acknowledgment_remarks' => $request->input('acknowledgment_remarks'),
                ]);
                Audit::log('duty.turnover_acknowledged', $turnover, 'Acknowledged turnover from '.$turnover->fromUser->displayName());
            }

            Audit::log('duty.started', $duty, $user->displayName().' started duty');
        });

        return redirect()->route('dashboard')->with('success', 'You are now the Duty Office Watch on duty.');
    }

    public function turnoverForm(Request $request, DutyPeriod $duty)
    {
        $this->authorizeTurnover($request->user(), $duty);

        return view('duty.turnover', [
            'duty' => $duty->load('user'),
            'pendingIncoming' => IncomingCommunication::pending()->orderBy('received_at')->get(),
            'pendingOutgoing' => OutgoingCommunication::pending()->orderBy('created_at')->get(),
            'counts' => $this->periodCounts($duty),
        ]);
    }

    /** Outgoing watch ends duty and turns over pending items. */
    public function turnover(Request $request, DutyPeriod $duty)
    {
        $user = $request->user();
        $this->authorizeTurnover($user, $duty);

        $data = $request->validate([
            'notes' => ['required', 'string', 'max:5000'],
            'confirm' => ['accepted'],
        ], ['confirm.accepted' => 'Confirm that the pending items listed are accurate.']);

        $snapshot = [
            'incoming' => IncomingCommunication::pending()->orderBy('received_at')->get()->map(fn ($r) => [
                'id' => $r->id,
                'reference_no' => $r->reference_no,
                'subject' => $r->sensitivity === 'unclassified' ? $r->subject : '['.$r->sensitivityLabel().']',
                'status' => $r->statusLabel(),
                'due' => $r->action_due_at?->format('d M Y H:i'),
            ])->all(),
            'outgoing' => OutgoingCommunication::pending()->orderBy('created_at')->get()->map(fn ($r) => [
                'id' => $r->id,
                'reference_no' => $r->reference_no,
                'subject' => $r->sensitivity === 'unclassified' ? $r->subject : '['.$r->sensitivityLabel().']',
                'status' => $r->statusLabel(),
                'destination' => $r->destination_office,
            ])->all(),
            'counts' => $this->periodCounts($duty),
        ];

        DB::transaction(function () use ($duty, $user, $data, $snapshot) {
            $turnover = DutyTurnover::create([
                'from_duty_period_id' => $duty->id,
                'from_user_id' => $duty->user_id,
                'turned_over_at' => now(),
                'notes' => $data['notes'],
                'pending_snapshot' => $snapshot,
            ]);
            $duty->update(['status' => DutyPeriod::COMPLETED, 'ended_at' => now()]);

            $by = $user->id === $duty->user_id ? '' : ' (recorded by '.$user->displayName().')';
            Audit::log('duty.turned_over', $turnover, $duty->user->displayName().' ended duty and turned over '
                .count($snapshot['incoming']).' incoming / '.count($snapshot['outgoing']).' outgoing pending items'.$by);
        });

        return redirect()->route('duty.show', $duty)->with('success', 'Duty ended. The turnover is waiting for the incoming watch to acknowledge it.');
    }

    public function show(DutyPeriod $duty)
    {
        $duty->load(['user', 'assigner', 'turnoverGiven.toUser', 'turnoverReceived.fromUser']);

        return view('duty.show', [
            'duty' => $duty,
            'incoming' => $duty->incoming()->orderBy('received_at')->get(),
            'outgoing' => $duty->outgoing()->orderBy('created_at')->get(),
            'events' => $duty->events()->with(['trackable', 'user'])->orderBy('occurred_at')->get(),
        ]);
    }

    private function authorizeTurnover(User $user, DutyPeriod $duty): void
    {
        abort_unless($duty->status === DutyPeriod::ACTIVE, 422, 'Only an active duty period can be turned over.');
    }

    /** @return array{incoming: int, outgoing: int, events: int} */
    private function periodCounts(DutyPeriod $duty): array
    {
        return [
            'incoming' => $duty->incoming()->count(),
            'outgoing' => $duty->outgoing()->count(),
            'events' => $duty->events()->count(),
        ];
    }
}
