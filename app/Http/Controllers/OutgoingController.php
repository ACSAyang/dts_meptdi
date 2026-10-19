<?php

namespace App\Http\Controllers;

use App\Models\OutgoingCommunication;
use App\Services\Audit;
use App\Services\Duty;
use App\Services\ReferenceNumber;
use App\Services\Tracker;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class OutgoingController extends Controller
{
    public function index(Request $request)
    {
        $records = OutgoingCommunication::query()
            ->search($request->input('q'))
            ->when($request->input('status') === 'pending', fn ($q) => $q->pending())
            ->when($request->input('status') && $request->input('status') !== 'pending', fn ($q) => $q->where('status', $request->input('status')))
            ->when($request->input('from'), fn ($q, $d) => $q->whereDate('created_at', '>=', $d))
            ->when($request->input('to'), fn ($q, $d) => $q->whereDate('created_at', '<=', $d))
            ->with('registrar')
            ->latest('id')
            ->paginate(25)->withQueryString();

        return view('outgoing.index', compact('records'));
    }

    public function create()
    {
        return view('outgoing.form', [
            'record' => new OutgoingCommunication(['document_date' => now(), 'sensitivity' => 'unclassified', 'priority' => 'routine', 'mode_of_dispatch' => 'liaison']),
            'nextReference' => ReferenceNumber::preview(OutgoingCommunication::REF_TYPE),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate($this->rules() + $this->attachmentRules());
        $user = $request->user();

        $record = DB::transaction(function () use ($data, $user, $request) {
            $record = OutgoingCommunication::create(
                collect($data)->only(OutgoingCommunication::CORRECTABLE)->all()
                + ReferenceNumber::next(OutgoingCommunication::REF_TYPE)
                + [
                    'status' => 'registered',
                    'registered_by' => $user->id,
                    'duty_period_id' => Duty::periodFor($user)?->id,
                ]
            );

            Tracker::event($record, 'registered', 'For '.$record->destination_office.($record->addressee ? ' — '.$record->addressee : ''));
            Audit::log('outgoing.registered', $record, 'Registered outgoing communication', null, $record->only(OutgoingCommunication::CORRECTABLE));
            Tracker::attach($record, $request->file('attachments'), 'document');

            return $record;
        });

        return redirect()->route('outgoing.show', $record)->with('success', 'Registered as '.$record->reference_no.'.');
    }

    public function show(Request $request, OutgoingCommunication $outgoing)
    {
        $outgoing->load(['events.user', 'attachments.uploader', 'deliveryAttempts.user', 'registrar', 'closer', 'canceller', 'verifier', 'dutyPeriod.user']);

        return view('outgoing.show', ['record' => $outgoing]);
    }

    public function edit(Request $request, OutgoingCommunication $outgoing)
    {

        return view('outgoing.form', ['record' => $outgoing, 'nextReference' => null]);
    }

    public function update(Request $request, OutgoingCommunication $outgoing)
    {

        $data = $request->validate($this->rules() + ['correction_reason' => ['required', 'string', 'max:1000']]);
        $outgoing->fill(collect($data)->only(OutgoingCommunication::CORRECTABLE)->all());

        if (! $outgoing->isDirty()) {
            return redirect()->route('outgoing.show', $outgoing)->with('warning', 'No changes were made.');
        }

        $changes = $outgoing->getDirty();
        $old = array_intersect_key($outgoing->getOriginal(), $changes);

        DB::transaction(function () use ($outgoing, $changes, $old, $data) {
            $outgoing->save();
            Tracker::event($outgoing, 'corrected', 'Corrected: '.implode(', ', array_map(fn ($f) => str_replace('_', ' ', $f), array_keys($changes))).' — Reason: '.$data['correction_reason']);
            Audit::log('outgoing.corrected', $outgoing, 'Corrected record', $old, $changes, $data['correction_reason']);
        });

        return redirect()->route('outgoing.show', $outgoing)->with('success', 'Correction saved and recorded in the history.');
    }

    /** Record handover to the liaison and dispatch from the office. */
    public function markDispatched(Request $request, OutgoingCommunication $outgoing)
    {
        $this->authorizeAction($request, $outgoing);
        abort_unless(in_array($outgoing->status, ['registered', 'follow_up'], true), 422, 'This communication has already been dispatched.');

        $data = $request->validate([
            'liaison_name' => [Rule::requiredIf($outgoing->mode_of_dispatch === 'liaison'), 'nullable', 'string', 'max:255'],
            'handed_over_at' => ['required', 'date'],
            'dispatched_at' => ['required', 'date', 'after_or_equal:handed_over_at'],
            'handover_remarks' => ['nullable', 'string', 'max:2000'],
        ]);

        DB::transaction(function () use ($outgoing, $data) {
            $redispatch = $outgoing->status === 'follow_up';
            $outgoing->update($data + ['status' => 'dispatched']);
            Tracker::event($outgoing, 'dispatched',
                ($redispatch ? 'Re-dispatched. ' : '')
                .'Handed over '.(($data['liaison_name'] ?? null) ? 'to '.$data['liaison_name'].' ' : '')
                .'at '.$outgoing->handed_over_at->format('d M H:i').'H; dispatched '.$outgoing->dispatched_at->format('d M H:i').'H.'
                .(($data['handover_remarks'] ?? null) ? ' '.$data['handover_remarks'] : ''));
            Audit::log('outgoing.dispatched', $outgoing, 'Dispatched', null, $data);
        });

        return back()->with('success', 'Dispatch recorded.');
    }

    public function attempt(Request $request, OutgoingCommunication $outgoing)
    {
        $this->authorizeAction($request, $outgoing);
        abort_unless(in_array($outgoing->status, ['dispatched', 'follow_up'], true), 422, 'Record the dispatch first.');

        $data = $request->validate([
            'attempted_at' => ['required', 'date'],
            'result' => ['required', Rule::in(array_keys(config('dts.delivery_results')))],
            'liaison_name' => ['nullable', 'string', 'max:255'],
            'remarks' => ['nullable', 'string', 'max:2000'],
        ]);
        $user = $request->user();

        DB::transaction(function () use ($outgoing, $data, $user) {
            $attempt = $outgoing->deliveryAttempts()->create($data + [
                'attempt_no' => $outgoing->deliveryAttempts()->count() + 1,
                'liaison_name' => $data['liaison_name'] ?? $outgoing->liaison_name,
                'user_id' => $user->id,
                'duty_period_id' => Duty::periodFor($user)?->id,
            ]);

            if ($data['result'] !== 'delivered') {
                $outgoing->update(['status' => 'follow_up']);
            }

            Tracker::event($outgoing, 'delivery_attempt', 'Attempt #'.$attempt->attempt_no.': '.$attempt->resultLabel().(($data['remarks'] ?? null) ? ' — '.$data['remarks'] : ''));
            Audit::log('outgoing.delivery_attempt', $outgoing, 'Delivery attempt #'.$attempt->attempt_no, null, $data);
        });

        return back()->with('success', $data['result'] === 'delivered'
            ? 'Delivery recorded. Now record the recipient details to confirm receipt.'
            : 'Delivery attempt recorded; the communication is marked for follow-up.');
    }

    /** Record recipient details (and optional proof) — receipt still needs verification before closure. */
    public function receive(Request $request, OutgoingCommunication $outgoing)
    {
        $this->authorizeAction($request, $outgoing);
        abort_unless(in_array($outgoing->status, ['dispatched', 'follow_up'], true), 422, 'Record the dispatch first.');

        $data = $request->validate([
            'received_by_name' => ['required', 'string', 'max:255'],
            'received_by_position' => ['nullable', 'string', 'max:255'],
            'received_at' => ['required', 'date', 'after_or_equal:'.$outgoing->dispatched_at?->format('Y-m-d H:i'), 'before_or_equal:'.now()->addMinutes(5)->toDateTimeString()],
            'receipt_remarks' => ['nullable', 'string', 'max:2000'],
            'proof' => ['nullable', 'array', 'max:5'],
            'proof.*' => ['file', 'mimes:'.config('dts.attachment_mimes'), 'max:'.config('dts.attachment_max_kb')],
        ], ['received_at.after_or_equal' => 'The receipt time cannot be earlier than the dispatch time.']);

        DB::transaction(function () use ($outgoing, $data, $request) {
            $outgoing->update(collect($data)->except('proof')->all() + ['status' => 'received']);
            Tracker::event($outgoing, 'received', 'Received by '.$data['received_by_name']
                .(($data['received_by_position'] ?? null) ? ', '.$data['received_by_position'] : '')
                .' on '.$outgoing->received_at->format('d M Y H:i').'H.'.(($data['receipt_remarks'] ?? null) ? ' '.$data['receipt_remarks'] : ''));
            Audit::log('outgoing.received', $outgoing, 'Receipt recorded', null, collect($data)->except('proof')->all());
            Tracker::attach($outgoing, $request->file('proof'), 'proof_of_receipt');
        });

        return back()->with('success', 'Receipt recorded. Verify the receipt details to close the record.');
    }

    public function verify(Request $request, OutgoingCommunication $outgoing)
    {
        $this->authorizeAction($request, $outgoing);
        if ($outgoing->status !== 'received' || ! $outgoing->received_by_name || ! $outgoing->received_at) {
            return back()->with('error', 'Recipient details must be recorded before receipt can be verified.');
        }

        $data = $request->validate([
            'verified' => ['accepted'],
            'verification_remarks' => ['nullable', 'string', 'max:2000'],
        ], ['verified.accepted' => 'Confirm that you have verified the receipt details and proof.']);
        $user = $request->user();

        DB::transaction(function () use ($outgoing, $data, $user) {
            $outgoing->update([
                'verification_remarks' => $data['verification_remarks'] ?? null,
                'verified_at' => now(), 'verified_by' => $user->id,
                'closed_at' => now(), 'closed_by' => $user->id,
                'status' => 'closed',
            ]);
            Tracker::event($outgoing, 'verified', 'Receipt verified and record closed.'.(! empty($data['verification_remarks']) ? ' '.$data['verification_remarks'] : ''));
            Audit::log('outgoing.verified', $outgoing, 'Receipt verified; closed', null, $data);
        });

        return back()->with('success', $outgoing->reference_no.' verified and closed.');
    }

    public function cancel(Request $request, OutgoingCommunication $outgoing)
    {
        $this->authorizeAction($request, $outgoing);
        $data = $request->validate(['cancel_reason' => ['required', 'string', 'max:1000']]);

        DB::transaction(function () use ($outgoing, $data, $request) {
            $outgoing->update(['status' => 'cancelled', 'cancelled_at' => now(), 'cancelled_by' => $request->user()->id, 'cancel_reason' => $data['cancel_reason']]);
            Tracker::event($outgoing, 'cancelled', 'Reason: '.$data['cancel_reason']);
            Audit::log('outgoing.cancelled', $outgoing, 'Cancelled', reason: $data['cancel_reason']);
        });

        return back()->with('success', $outgoing->reference_no.' cancelled. The record and its history are retained.');
    }

    /** Reopen a closed or cancelled record (reason required). */
    public function reopen(Request $request, OutgoingCommunication $outgoing)
    {
        abort_if($outgoing->isOpen(), 422);
        $data = $request->validate(['reopen_reason' => ['required', 'string', 'max:1000']]);

        DB::transaction(function () use ($outgoing, $data) {
            $previous = $outgoing->status;
            $status = $outgoing->received_at ? 'received' : ($outgoing->dispatched_at ? 'follow_up' : 'registered');
            $outgoing->update([
                'status' => $status,
                'verified_at' => null, 'verified_by' => null, 'closed_at' => null, 'closed_by' => null,
                'cancelled_at' => null, 'cancelled_by' => null,
            ]);
            Tracker::event($outgoing, 'reopened', 'Reason: '.$data['reopen_reason']);
            Audit::log('outgoing.reopened', $outgoing, 'Reopened (was '.$previous.')', ['status' => $previous], ['status' => $status], $data['reopen_reason']);
        });

        return back()->with('success', 'Record reopened.');
    }

    public function attach(Request $request, OutgoingCommunication $outgoing)
    {
        $request->validate($this->attachmentRules(true) + [
            'category' => ['required', Rule::in(array_keys(config('dts.attachment_categories')))],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        DB::transaction(fn () => Tracker::attach($outgoing, $request->file('attachments'), $request->input('category'), $request->input('description')));

        return back()->with('success', 'Attachment uploaded.');
    }

    private function rules(): array
    {
        return [
            'document_date' => ['nullable', 'date'],
            'document_type' => ['required', Rule::in(config('dts.document_types'))],
            'subject' => ['required', 'string', 'max:2000'],
            'originating_office' => ['nullable', 'string', 'max:255'],
            'signatory' => ['nullable', 'string', 'max:255'],
            'destination_office' => ['required', 'string', 'max:255'],
            'addressee' => ['nullable', 'string', 'max:255'],
            'destination_address' => ['nullable', 'string', 'max:1000'],
            'sensitivity' => ['required', Rule::in(array_keys(config('dts.sensitivities')))],
            'priority' => ['required', Rule::in(array_keys(config('dts.priorities')))],
            'mode_of_dispatch' => ['required', Rule::in(array_keys(config('dts.dispatch_modes')))],
            'enclosures' => ['nullable', 'string', 'max:255'],
            'remarks' => ['nullable', 'string', 'max:2000'],
            'liaison_name' => ['nullable', 'string', 'max:255'],
        ];
    }

    private function attachmentRules(bool $required = false): array
    {
        return [
            'attachments' => [$required ? 'required' : 'nullable', 'array', 'max:10'],
            'attachments.*' => ['file', 'mimes:'.config('dts.attachment_mimes'), 'max:'.config('dts.attachment_max_kb')],
        ];
    }

    private function authorizeAction(Request $request, OutgoingCommunication $record): void
    {
        abort_unless($record->isOpen(), 422, 'This record is already '.$record->statusLabel().'.');
    }
}
