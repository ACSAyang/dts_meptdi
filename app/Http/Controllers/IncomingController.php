<?php

namespace App\Http\Controllers;

use App\Models\IncomingCommunication;
use App\Services\Audit;
use App\Services\Duty;
use App\Services\ReferenceNumber;
use App\Services\Tracker;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class IncomingController extends Controller
{
    public function index(Request $request)
    {
        $records = IncomingCommunication::query()
            ->search($request->input('q'))
            ->when($request->input('status') === 'pending', fn ($q) => $q->pending())
            ->when($request->input('status') && $request->input('status') !== 'pending', fn ($q) => $q->where('status', $request->input('status')))
            ->when($request->input('sensitivity'), fn ($q, $s) => $q->where('sensitivity', $s))
            ->when($request->input('from'), fn ($q, $d) => $q->whereDate('received_at', '>=', $d))
            ->when($request->input('to'), fn ($q, $d) => $q->whereDate('received_at', '<=', $d))
            ->when($request->boolean('overdue'), fn ($q) => $q->pending()->whereNotNull('action_due_at')->where('action_due_at', '<', now()))
            ->with('registrar')
            ->latest('received_at')->latest('id')
            ->paginate(25)->withQueryString();

        return view('incoming.index', compact('records'));
    }

    public function create()
    {
        return view('incoming.form', [
            'record' => new IncomingCommunication(['received_at' => now(), 'sensitivity' => 'unclassified', 'priority' => 'routine']),
            'nextReference' => ReferenceNumber::preview(IncomingCommunication::REF_TYPE),
        ]);
    }

    public function store(Request $request)
    {
        $request->merge(['requires_action' => $request->boolean('requires_action')]);
        $data = $request->validate($this->rules() + $this->attachmentRules());
        $user = $request->user();

        $record = DB::transaction(function () use ($data, $user, $request) {
            $record = IncomingCommunication::create(
                collect($data)->only(IncomingCommunication::CORRECTABLE)->all()
                + ReferenceNumber::next(IncomingCommunication::REF_TYPE)
                + [
                    'status' => 'registered',
                    'registered_by' => $user->id,
                    'duty_period_id' => Duty::periodFor($user)?->id,
                ]
            );

            Tracker::event($record, 'registered', 'Received from '.$record->sender_name.($record->sender_office ? ' ('.$record->sender_office.')' : ''));
            Audit::log('incoming.registered', $record, 'Registered incoming communication', null, $record->only(IncomingCommunication::CORRECTABLE));
            Tracker::attach($record, $request->file('attachments'), 'document');

            return $record;
        });

        return redirect()->route('incoming.show', $record)->with('success', 'Registered as '.$record->reference_no.'.');
    }

    public function show(Request $request, IncomingCommunication $incoming)
    {
        $incoming->load(['events.user', 'attachments.uploader', 'registrar', 'closer', 'canceller', 'dutyPeriod.user']);

        return view('incoming.show', ['record' => $incoming]);
    }

    public function edit(Request $request, IncomingCommunication $incoming)
    {

        return view('incoming.form', ['record' => $incoming, 'nextReference' => null]);
    }

    /** Corrections require a reason; old and new values are kept in the audit trail. */
    public function update(Request $request, IncomingCommunication $incoming)
    {

        $request->merge(['requires_action' => $request->boolean('requires_action')]);
        $data = $request->validate($this->rules() + ['correction_reason' => ['required', 'string', 'max:1000']]);
        $incoming->fill(collect($data)->only(IncomingCommunication::CORRECTABLE)->all());

        if (! $incoming->isDirty()) {
            return redirect()->route('incoming.show', $incoming)->with('warning', 'No changes were made.');
        }

        $changes = $incoming->getDirty();
        $old = array_intersect_key($incoming->getOriginal(), $changes);

        DB::transaction(function () use ($incoming, $changes, $old, $data) {
            $incoming->save();
            Tracker::event($incoming, 'corrected', 'Corrected: '.implode(', ', array_map(fn ($f) => str_replace('_', ' ', $f), array_keys($changes))).' — Reason: '.$data['correction_reason']);
            Audit::log('incoming.corrected', $incoming, 'Corrected record', $old, $changes, $data['correction_reason']);
        });

        return redirect()->route('incoming.show', $incoming)->with('success', 'Correction saved and recorded in the history.');
    }

    public function route(Request $request, IncomingCommunication $incoming)
    {
        $this->authorizeAction($request, $incoming);
        $data = $request->validate([
            'routed_to' => ['required', 'string', 'max:255'],
            'instructions' => ['nullable', 'string', 'max:2000'],
            'action_due_at' => ['nullable', 'date'],
        ]);

        DB::transaction(function () use ($incoming, $data) {
            $incoming->update([
                'routed_to' => $data['routed_to'],
                'routed_at' => now(),
                'action_due_at' => $data['action_due_at'] ?? $incoming->action_due_at,
                'status' => $incoming->status === 'registered' ? 'routed' : $incoming->status,
            ]);
            Tracker::event($incoming, 'routed', $data['instructions'] ?? null, ['routed_to' => $data['routed_to']]);
            Audit::log('incoming.routed', $incoming, 'Routed to '.$data['routed_to'], null, $data);
        });

        return back()->with('success', 'Routing recorded.');
    }

    public function actionUpdate(Request $request, IncomingCommunication $incoming)
    {
        $this->authorizeAction($request, $incoming);
        $data = $request->validate(['description' => ['required', 'string', 'max:2000']]);

        DB::transaction(function () use ($incoming, $data) {
            $incoming->update(['status' => 'in_action']);
            Tracker::event($incoming, 'action_update', $data['description']);
            Audit::log('incoming.action_update', $incoming, 'Action update recorded', null, $data);
        });

        return back()->with('success', 'Action update recorded.');
    }

    public function close(Request $request, IncomingCommunication $incoming)
    {
        $this->authorizeAction($request, $incoming);

        if ($incoming->requires_action && ! $incoming->routed_at) {
            return back()->with('error', 'This communication requires action. Route it and record the action taken before closing.');
        }

        $data = $request->validate([
            'action_taken' => [Rule::requiredIf($incoming->requires_action), 'nullable', 'string', 'max:5000'],
            'outcome' => ['required', 'string', 'max:5000'],
        ], [
            'action_taken.required' => 'Record the action taken — this communication requires action.',
            'outcome.required' => 'Record the outcome/disposition before closing.',
        ]);

        DB::transaction(function () use ($incoming, $data, $request) {
            $incoming->update($data + ['status' => 'closed', 'closed_at' => now(), 'closed_by' => $request->user()->id]);
            Tracker::event($incoming, 'closed', 'Outcome: '.$data['outcome']);
            Audit::log('incoming.closed', $incoming, 'Closed', null, $data);
        });

        return back()->with('success', $incoming->reference_no.' closed.');
    }

    public function cancel(Request $request, IncomingCommunication $incoming)
    {
        $this->authorizeAction($request, $incoming);
        $data = $request->validate(['cancel_reason' => ['required', 'string', 'max:1000']]);

        DB::transaction(function () use ($incoming, $data, $request) {
            $incoming->update(['status' => 'cancelled', 'cancelled_at' => now(), 'cancelled_by' => $request->user()->id, 'cancel_reason' => $data['cancel_reason']]);
            Tracker::event($incoming, 'cancelled', 'Reason: '.$data['cancel_reason']);
            Audit::log('incoming.cancelled', $incoming, 'Cancelled', reason: $data['cancel_reason']);
        });

        return back()->with('success', $incoming->reference_no.' cancelled. The record and its history are retained.');
    }

    /** Reopen a closed or cancelled record (reason required). */
    public function reopen(Request $request, IncomingCommunication $incoming)
    {
        abort_if($incoming->isOpen(), 422);
        $data = $request->validate(['reopen_reason' => ['required', 'string', 'max:1000']]);

        DB::transaction(function () use ($incoming, $data) {
            $previous = $incoming->status;
            $incoming->update([
                'status' => $incoming->routed_at ? 'in_action' : 'registered',
                'closed_at' => null, 'closed_by' => null, 'cancelled_at' => null, 'cancelled_by' => null,
            ]);
            Tracker::event($incoming, 'reopened', 'Reason: '.$data['reopen_reason']);
            Audit::log('incoming.reopened', $incoming, 'Reopened (was '.$previous.')', ['status' => $previous], ['status' => $incoming->status], $data['reopen_reason']);
        });

        return back()->with('success', 'Record reopened.');
    }

    public function attach(Request $request, IncomingCommunication $incoming)
    {
        $request->validate($this->attachmentRules(true) + [
            'category' => ['required', Rule::in(array_keys(config('dts.attachment_categories')))],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        DB::transaction(fn () => Tracker::attach($incoming, $request->file('attachments'), $request->input('category'), $request->input('description')));

        return back()->with('success', 'Attachment uploaded.');
    }

    private function rules(): array
    {
        return [
            'received_at' => ['required', 'date', 'before_or_equal:'.now()->addMinutes(5)->toDateTimeString()],
            'sender_name' => ['required', 'string', 'max:255'],
            'sender_office' => ['nullable', 'string', 'max:255'],
            'sender_reference' => ['nullable', 'string', 'max:255'],
            'document_date' => ['nullable', 'date'],
            'document_type' => ['required', Rule::in(config('dts.document_types'))],
            'subject' => ['required', 'string', 'max:2000'],
            'sensitivity' => ['required', Rule::in(array_keys(config('dts.sensitivities')))],
            'priority' => ['required', Rule::in(array_keys(config('dts.priorities')))],
            'mode_of_receipt' => ['nullable', Rule::in(array_keys(config('dts.receipt_modes')))],
            'enclosures' => ['nullable', 'string', 'max:255'],
            'handling_remarks' => ['nullable', 'string', 'max:2000'],
            'requires_action' => ['boolean'],
            'action_required' => ['nullable', 'required_if:requires_action,1', 'string', 'max:2000'],
            'action_due_at' => ['nullable', 'date'],
        ];
    }

    private function attachmentRules(bool $required = false): array
    {
        return [
            'attachments' => [$required ? 'required' : 'nullable', 'array', 'max:10'],
            'attachments.*' => ['file', 'mimes:'.config('dts.attachment_mimes'), 'max:'.config('dts.attachment_max_kb')],
        ];
    }

    private function authorizeAction(Request $request, IncomingCommunication $record): void
    {
        abort_unless($record->isOpen(), 422, 'This record is already '.$record->statusLabel().'.');
    }
}
