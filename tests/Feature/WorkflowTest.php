<?php

namespace Tests\Feature;

use App\Models\DutyPeriod;
use App\Models\DutyTurnover;
use App\Models\IncomingCommunication;
use App\Models\OutgoingCommunication;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class WorkflowTest extends TestCase
{
    use RefreshDatabase;

    private User $watch1;

    private User $watch2;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        $this->watch1 = User::factory()->create(['username' => 'w1']);
        $this->watch2 = User::factory()->create(['username' => 'w2']);
    }

    private function startDuty(User $watch, bool $acknowledge = false): DutyPeriod
    {
        $this->actingAs($watch)->post(route('duty.start'), array_filter([
            'scheduled_end' => now()->addDay()->format('Y-m-d H:i'),
            'acknowledge' => $acknowledge ? 1 : null,
        ]))->assertRedirect(route('dashboard'));

        return DutyPeriod::current();
    }

    private function incomingPayload(array $overrides = []): array
    {
        return array_merge([
            'received_at' => now()->subMinutes(5)->format('Y-m-d H:i'),
            'sender_name' => 'CO, Coast Guard District NCR',
            'sender_office' => 'CGDNCR',
            'document_type' => 'Memorandum',
            'subject' => 'Request for training slots',
            'sensitivity' => 'unclassified',
            'priority' => 'routine',
            'requires_action' => 1,
            'action_required' => 'Reply with available slots',
        ], $overrides);
    }

    private function outgoingPayload(array $overrides = []): array
    {
        return array_merge([
            'document_type' => 'Letter',
            'subject' => 'Submission of training report',
            'destination_office' => 'HPCG',
            'sensitivity' => 'unclassified',
            'priority' => 'routine',
            'mode_of_dispatch' => 'liaison',
            'liaison_name' => 'SN1 Cruz',
        ], $overrides);
    }

    public function test_login_and_inactive_users_are_rejected(): void
    {
        $this->post(route('login'), ['username' => 'w1', 'password' => 'password'])->assertRedirect(route('dashboard'));
        $this->post(route('logout'));

        $this->watch2->update(['is_active' => false]);
        $this->post(route('login'), ['username' => 'w2', 'password' => 'password'])->assertSessionHasErrors('username');
        $this->assertGuest();
    }

    public function test_every_account_has_the_same_access(): void
    {
        $this->startDuty($this->watch1);
        foreach (['dashboard', 'incoming.index', 'outgoing.index', 'duty.index', 'reports.index',
            'admin.users.index', 'admin.audit.index', 'admin.backups.index', 'incoming.create', 'outgoing.create'] as $route) {
            $this->actingAs($this->watch2)->get(route($route))->assertOk();
        }
    }

    public function test_any_user_creates_account_with_serial_number(): void
    {
        $this->actingAs($this->watch1)->post(route('admin.users.store'), [
            'rank' => 'SN1', 'name' => 'Juan Cruz', 'serial_number' => '2-1234', 'username' => 'jcruz',
            'is_active' => 1, 'password' => 'Secret@1234', 'password_confirmation' => 'Secret@1234',
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('users', ['username' => 'jcruz', 'serial_number' => '2-1234']);
        $this->actingAs($this->watch1)->get(route('admin.users.index'))->assertSee('Serial no.')->assertSee('2-1234');
    }

    public function test_someone_must_be_on_duty_before_recording(): void
    {
        $this->actingAs($this->watch2)->post(route('incoming.store'), $this->incomingPayload())
            ->assertRedirect(route('duty.index'))->assertSessionHas('error');
        $this->actingAs($this->watch2)->get(route('outgoing.create'))->assertRedirect(route('duty.index'));
        $this->assertSame(0, IncomingCommunication::count());

        // Once a watch is on duty, any user can record; entries link to that watch.
        $duty = $this->startDuty($this->watch1);
        $this->actingAs($this->watch2)->post(route('incoming.store'), $this->incomingPayload());

        $record = IncomingCommunication::firstOrFail();
        $this->assertSame($duty->id, $record->duty_period_id);
        $this->assertSame($this->watch2->id, $record->registered_by);
    }

    public function test_reference_numbers_are_separate_sequences_with_office_prefix(): void
    {
        $this->startDuty($this->watch1);
        $year = now()->year;

        $this->actingAs($this->watch1)->post(route('incoming.store'), $this->incomingPayload());
        $this->actingAs($this->watch1)->post(route('incoming.store'), $this->incomingPayload());
        $this->actingAs($this->watch1)->post(route('outgoing.store'), $this->outgoingPayload());

        $this->assertSame(["MEPTDI-IN-{$year}-0001", "MEPTDI-IN-{$year}-0002"], IncomingCommunication::orderBy('id')->pluck('reference_no')->all());
        $this->assertSame("MEPTDI-OUT-{$year}-0001", OutgoingCommunication::first()->reference_no);
    }

    public function test_incoming_full_workflow_and_closure_rules(): void
    {
        $duty = $this->startDuty($this->watch1);
        $this->actingAs($this->watch1)->post(route('incoming.store'), $this->incomingPayload([
            'attachments' => [UploadedFile::fake()->create('memo.pdf', 100, 'application/pdf')],
        ]));
        $record = IncomingCommunication::firstOrFail();
        $this->assertSame($duty->id, $record->duty_period_id);
        $this->assertCount(1, $record->attachments);

        // Requires action: cannot close before it is forwarded.
        $this->post(route('incoming.close', $record), ['action_taken' => 'x', 'outcome' => 'y'])->assertSessionHas('error');
        $this->assertSame('registered', $record->fresh()->status);

        $this->post(route('incoming.route', $record), ['routed_to' => 'Training Division', 'action_due_at' => now()->addDay()->format('Y-m-d H:i')]);
        $this->assertSame('routed', $record->fresh()->status);
        $this->get(route('incoming.show', $record))->assertOk()->assertSee('Record the result and close');

        $this->post(route('incoming.action', $record), ['description' => 'Draft reply prepared']);
        $this->assertSame('in_action', $record->fresh()->status);

        // Action taken and outcome are both required.
        $this->post(route('incoming.close', $record), ['outcome' => 'Done'])->assertSessionHasErrors('action_taken');
        $this->post(route('incoming.close', $record), ['action_taken' => 'Reply sent', 'outcome' => 'Complied'])->assertSessionHasNoErrors();

        $record->refresh();
        $this->assertSame('closed', $record->status);
        $this->assertSame(['registered', 'attachment', 'routed', 'action_update', 'closed'], $record->events()->pluck('event')->all());

        // Closed records cannot be acted on, but any user can correct or reopen them (with a reason).
        $this->post(route('incoming.cancel', $record), ['cancel_reason' => 'x'])->assertStatus(422);
        $this->actingAs($this->watch2)->get(route('incoming.edit', $record))->assertOk();
        $this->actingAs($this->watch2)->post(route('incoming.reopen', $record), ['reopen_reason' => 'Further compliance needed'])->assertSessionHasNoErrors();
        $this->assertSame('in_action', $record->fresh()->status);
    }

    public function test_corrections_and_cancellations_require_reason_and_keep_history(): void
    {
        $this->startDuty($this->watch1);
        $this->actingAs($this->watch1)->post(route('incoming.store'), $this->incomingPayload(['requires_action' => 0]));
        $record = IncomingCommunication::firstOrFail();

        $this->put(route('incoming.update', $record), $this->incomingPayload(['subject' => 'Corrected subject', 'requires_action' => 0]))
            ->assertSessionHasErrors('correction_reason');
        $this->put(route('incoming.update', $record), $this->incomingPayload(['subject' => 'Corrected subject', 'requires_action' => 0, 'correction_reason' => 'Typo']))
            ->assertSessionHasNoErrors();

        $this->assertSame('Corrected subject', $record->fresh()->subject);
        $this->assertDatabaseHas('audit_logs', ['action' => 'incoming.corrected', 'reason' => 'Typo', 'auditable_id' => $record->id]);

        $this->post(route('incoming.cancel', $record), [])->assertSessionHasErrors('cancel_reason');
        $this->post(route('incoming.cancel', $record), ['cancel_reason' => 'Duplicate entry'])->assertSessionHasNoErrors();
        $this->assertSame('cancelled', $record->fresh()->status);
        $this->assertDatabaseHas('incoming_communications', ['id' => $record->id]); // never deleted
    }

    public function test_outgoing_full_workflow_requires_receipt_and_verification(): void
    {
        $this->startDuty($this->watch1);
        $this->actingAs($this->watch1)->post(route('outgoing.store'), $this->outgoingPayload());
        $record = OutgoingCommunication::firstOrFail();

        // Cannot record receipt or verify before dispatch.
        $this->post(route('outgoing.receive', $record), ['received_by_name' => 'X', 'received_at' => now()->format('Y-m-d H:i')])->assertStatus(422);
        $this->post(route('outgoing.verify', $record), ['verified' => 1])->assertSessionHas('error');

        $this->post(route('outgoing.dispatch', $record), [
            'liaison_name' => 'SN1 Cruz',
            'handed_over_at' => now()->subHours(3)->format('Y-m-d H:i'),
            'dispatched_at' => now()->subHours(3)->format('Y-m-d H:i'),
        ])->assertSessionHasNoErrors();
        $this->assertSame('dispatched', $record->fresh()->status);

        $this->post(route('outgoing.attempt', $record), ['attempted_at' => now()->subHours(2)->format('Y-m-d H:i'), 'result' => 'failed', 'remarks' => 'Office closed']);
        $this->assertSame('follow_up', $record->fresh()->status);

        // Recipient name is required.
        $this->post(route('outgoing.receive', $record), ['received_at' => now()->subHour()->format('Y-m-d H:i')])->assertSessionHasErrors('received_by_name');
        $this->post(route('outgoing.receive', $record), [
            'received_by_name' => 'PO2 Santos',
            'received_by_position' => 'Admin NCO, HPCG',
            'received_at' => now()->subHour()->format('Y-m-d H:i'),
            'proof' => [UploadedFile::fake()->image('receipt.jpg')],
        ])->assertSessionHasNoErrors();
        $record->refresh();
        $this->assertSame('received', $record->status);
        $this->assertSame('proof_of_receipt', $record->attachments()->first()->category);
        $this->get(route('outgoing.show', $record))->assertOk()->assertSee('Check the receipt and close');

        // Verification must be confirmed.
        $this->post(route('outgoing.verify', $record), [])->assertSessionHasErrors('verified');
        $this->post(route('outgoing.verify', $record), ['verified' => 1])->assertSessionHasNoErrors();
        $record->refresh();
        $this->assertSame('closed', $record->status);
        $this->assertSame($this->watch1->id, $record->verified_by);
        $this->assertNotNull($record->handed_over_at);
        $this->assertNotNull($record->dispatched_at);
        $this->assertNotNull($record->received_at);
        $this->assertNotNull($record->closed_at);
    }

    public function test_one_watch_at_a_time_and_turnover_must_be_acknowledged(): void
    {
        $first = $this->startDuty($this->watch1);
        $this->actingAs($this->watch1)->post(route('incoming.store'), $this->incomingPayload());

        // Second watch cannot start while the first is on duty.
        $this->actingAs($this->watch2)->post(route('duty.start'), ['scheduled_end' => now()->addDay()->format('Y-m-d H:i')])
            ->assertSessionHas('error');
        $this->assertSame(1, DutyPeriod::count());

        // Turnover needs notes and confirmation.
        $this->actingAs($this->watch1)->post(route('duty.turnover.store', $first), ['notes' => 'All quiet'])->assertSessionHasErrors('confirm');
        $this->actingAs($this->watch1)->post(route('duty.turnover.store', $first), ['notes' => 'All quiet', 'confirm' => 1])->assertSessionHasNoErrors();
        $this->assertSame('completed', $first->fresh()->status);

        $turnover = DutyTurnover::firstOrFail();
        $this->assertCount(1, $turnover->pending_snapshot['incoming']);

        // Incoming watch must acknowledge.
        $this->actingAs($this->watch2)->post(route('duty.start'), ['scheduled_end' => now()->addDay()->format('Y-m-d H:i')])
            ->assertSessionHasErrors('acknowledge');
        $second = $this->startDuty($this->watch2, acknowledge: true);

        $turnover->refresh();
        $this->assertSame($this->watch2->id, $turnover->to_user_id);
        $this->assertSame($second->id, $turnover->to_duty_period_id);
        $this->assertNotNull($turnover->acknowledged_at);
    }

    public function test_confidential_records_are_open_to_every_user(): void
    {
        $this->startDuty($this->watch1);
        $this->actingAs($this->watch1)->post(route('incoming.store'), $this->incomingPayload([
            'sensitivity' => 'confidential',
            'subject' => 'Confidential matter',
            'attachments' => [UploadedFile::fake()->create('c.pdf', 10, 'application/pdf')],
        ]));
        $record = IncomingCommunication::firstOrFail();

        $this->actingAs($this->watch2)->get(route('incoming.index'))->assertSee('Confidential matter');
        $this->actingAs($this->watch2)->get(route('incoming.show', $record))->assertOk()->assertSee('Confidential matter');
        $this->actingAs($this->watch2)->get(route('attachments.show', $record->attachments->first()))->assertOk();

        // ...and any user can act on them and close them.
        $this->actingAs($this->watch2)->post(route('incoming.route', $record), ['routed_to' => 'Commandant'])->assertSessionHasNoErrors();
        $this->actingAs($this->watch2)->post(route('incoming.close', $record), ['action_taken' => 'Delivered', 'outcome' => 'Noted'])->assertSessionHasNoErrors();
        $this->assertSame('closed', $record->fresh()->status);
    }

    public function test_reports_and_record_pages_render(): void
    {
        $this->startDuty($this->watch1);
        $this->actingAs($this->watch1)->post(route('incoming.store'), $this->incomingPayload());
        $this->actingAs($this->watch1)->post(route('outgoing.store'), $this->outgoingPayload());

        foreach (['reports.daily', 'reports.pending', 'reports.delivery', 'reports.duty'] as $route) {
            $this->actingAs($this->watch2)->get(route($route))->assertOk()
                ->assertSee('pcg-logo.png')->assertSee('mepcom-logo.png')->assertSee('meptdi-logo.png');
        }
        $this->actingAs($this->watch2)->get(route('search', ['q' => 'training']))->assertOk()->assertSee('MEPTDI-IN-');
        $this->actingAs($this->watch2)->get(route('incoming.show', IncomingCommunication::first()))->assertOk();
        $this->actingAs($this->watch2)->get(route('outgoing.show', OutgoingCommunication::first()))->assertOk();
        $this->actingAs($this->watch2)->get(route('duty.show', DutyPeriod::first()))->assertOk();
    }
}
