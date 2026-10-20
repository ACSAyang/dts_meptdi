<?php

namespace App\Http\Controllers;

use App\Models\IncomingCommunication;
use App\Models\OutgoingCommunication;
use App\Models\RecordEvent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Document tracking history on a calendar: every tracking step of every
 * incoming and outgoing communication, on the day it happened.
 */
class CalendarController extends Controller
{
    /** Colour of each tracking step on the calendar. */
    public const COLORS = [
        'registered' => '#142f58',
        'routed' => '#0284c7',
        'dispatched' => '#0284c7',
        'action_update' => '#d97706',
        'delivery_attempt' => '#ea580c',
        'received' => '#4f46e5',
        'verified' => '#059669',
        'closed' => '#059669',
        'cancelled' => '#dc2626',
        'reopened' => '#b45309',
        'corrected' => '#7c3aed',
        'attachment' => '#64748b',
    ];

    private const MAX_EVENTS = 3000;

    public function index(Request $request)
    {
        return view('calendar.index', [
            'initialDate' => $request->date('date')?->toDateString(),
            'reference' => $request->input('ref'),
        ]);
    }

    /** Events feed for FullCalendar (?start=…&end=…&type=incoming|outgoing&step=…&ref=…). */
    public function events(Request $request): JsonResponse
    {
        $request->validate([
            'start' => ['required', 'date'],
            'end' => ['required', 'date', 'after:start'],
        ]);

        $types = match ($request->input('type')) {
            'incoming' => [IncomingCommunication::class],
            'outgoing' => [OutgoingCommunication::class],
            default => [IncomingCommunication::class, OutgoingCommunication::class],
        };
        $types = array_map(fn ($class) => (new $class)->getMorphClass(), $types);

        $events = RecordEvent::query()
            ->with(['user', 'trackable'])
            ->whereIn('trackable_type', $types)
            ->whereBetween('occurred_at', [Carbon::parse($request->input('start')), Carbon::parse($request->input('end'))])
            ->when($request->input('step'), fn ($q, $step) => $q->whereIn('event', explode(',', $step)))
            ->when($request->input('ref'), fn ($q, $ref) => $q->whereHasMorph('trackable', '*',
                fn (Builder $r) => $r->where('reference_no', 'like', '%'.$ref.'%')))
            ->orderBy('occurred_at')
            ->limit(self::MAX_EVENTS)
            ->get()
            ->filter(fn (RecordEvent $e) => $e->trackable !== null)
            ->map(function (RecordEvent $e) {
                $record = $e->trackable;
                $isIncoming = $record instanceof IncomingCommunication;
                // Short reference for the calendar cell, e.g. "IN-2026-0007".
                $short = Str::after($record->reference_no, config('dts.office_code').'-');

                return [
                    'id' => $e->id,
                    'title' => $short.' · '.$e->label(),
                    'start' => $e->occurred_at->toIso8601String(),
                    'url' => route($isIncoming ? 'incoming.show' : 'outgoing.show', $record),
                    'color' => self::COLORS[$e->event] ?? '#64748b',
                    'extendedProps' => [
                        'reference' => $record->reference_no,
                        'direction' => $isIncoming ? 'Incoming' : 'Outgoing',
                        'step' => $e->label(),
                        'subject' => Str::limit($record->subject, 90),
                        'by' => $e->user?->displayName(),
                    ],
                ];
            })
            ->values();

        return response()->json($events);
    }
}
