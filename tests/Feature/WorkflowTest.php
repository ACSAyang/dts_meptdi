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
            'admin.users.index', 'admin.audit.index', 'admin.backups.index', 'incoming.create', 'outgoing.create', 'calendar.index'] as $route) {
            $this->actingAs($this->watch2)->get(route($route))->assertOk();
        }
    }

    public function test_any_user_creates_account_with_rank_split_name_and_pcg_suffix(): void
    {
        $this->actingAs($this->watch1)->post(route('admin.users.store'), [
            'rank' => 'ASN', 'first_name' => 'Ian Russel', 'middle_initial' => 'u.', 'last_name' => 'Pascual',
            'serial_number' => '2-1234', 'username' => 'ipascual', 'email' => '',
            'is_active' => 1, 'password' => 'Secret@1234', 'password_confirmation' => 'Secret@1234',
        ])->assertSessionHasNoErrorsIn('user');

        $user = User::where('username', 'ipascual')->firstOrFail();
        $this->assertSame('U', $user->middle_initial);
        $this->assertSame('Ian Russel U Pascual', $user->name);
        $this->assertSame('ASN Ian Russel U Pascual PCG', $user->displayName());
        $this->actingAs($this->watch1)->get(route('admin.users.index'))
            ->assertSee('ASN Ian Russel U Pascual PCG')->assertSee('2-1234')->assertSee('modal-user');
    }

    public function test_rank_must_be_a_pcg_rank_and_errors_reopen_the_add_user_popup(): void
    {
        $this->actingAs($this->watch1)->from(route('admin.users.index'))->post(route('admin.users.store'), [
            'rank' => 'GENERAL', 'first_name' => 'Juan', 'last_name' => 'Cruz', 'serial_number' => '1',
            'username' => 'jc', 'password' => 'Secret@1234', 'password_confirmation' => 'Secret@1234',
        ])->assertRedirect(route('admin.users.index'))->assertSessionHasErrorsIn('user', 'rank');

        $this->assertSame(['ASN', 'SN2', 'SN1'], array_slice(array_keys(User::ranks()), 0, 3));
        $this->assertSame('ADM', array_key_last(User::ranks()));
        $this->actingAs($this->watch1)->get(route('admin.users.index'))->assertSee('data-auto-open', false);
    }

    public function test_registers_load_through_datatables_and_registration_popups_are_on_every_page(): void
    {
        $this->startDuty($this->watch1);
        $this->actingAs($this->watch1)->post(route('incoming.store'), $this->incomingPayload());
        $this->actingAs($this->watch1)->post(route('outgoing.store'), $this->outgoingPayload());

        $this->actingAs($this->watch1)->get(route('dashboard'))
            ->assertSee('id="modal-incoming"', false)->assertSee('id="modal-outgoing"', false)->assertSee('Version 1.0');

        foreach (['incoming.index' => 'Request for training slots', 'outgoing.index' => 'Submission of training report'] as $route => $subject) {
            $json = $this->actingAs($this->watch1)->getJson(route($route, ['draw' => 3, 'start' => 0, 'length' => 10, 'search' => ['value' => 'train']]))
                ->assertOk()->json();
            $this->assertSame(3, $json['draw']);
            $this->assertSame(1, $json['recordsFiltered']);
            $this->assertStringContainsString($subject, $json['data'][0]['3']);
            $this->assertArrayHasKey('data-href', $json['data'][0]['DT_RowAttr']);
        }

        $none = $this->actingAs($this->watch1)->getJson(route('incoming.index', ['draw' => 1, 'search' => ['value' => 'zzz-no-match']]))->json();
        $this->assertSame(0, $none['recordsFiltered']);
        $this->actingAs($this->watch1)->getJson(route('admin.audit.index', ['draw' => 1, 'length' => 5]))->assertOk()->assertJsonPath('draw', 1);
    }

    public function test_tracking_calendar_lists_every_tracking_step(): void
    {
        $this->startDuty($this->watch1);
        $this->actingAs($this->watch1)->post(route('incoming.store'), $this->incomingPayload());
        $record = IncomingCommunication::firstOrFail();
        $this->post(route('incoming.route', $record), ['routed_to' => 'Training Division']);

        $this->get(route('calendar.index'))->assertOk()->assertSee('data-tracking-calendar', false);

        $range = ['start' => now()->subMonth()->toDateString(), 'end' => now()->addMonth()->toDateString()];
        $events = $this->getJson(route('calendar.events', $range))->assertOk()->json();
        $this->assertSame(['Registered', 'Routed for action'], array_column(array_column($events, 'extendedProps'), 'step'));
        $this->assertSame(route('incoming.show', $record), $events[0]['url']);

        $this->assertCount(0, $this->getJson(route('calendar.events', $range + ['type' => 'outgoing']))->json());
        $this->assertCount(1, $this->getJson(route('calendar.events', $range + ['step' => 'routed']))->json());
        $this->assertCount(0, $this->getJson(route('calendar.events', $range + ['ref' => 'OUT-']))->json());
    }

    public function test_sample_data_seeder_adds_ten_of_each_once(): void
    {
        $this->seed(\Database\Seeders\SampleDataSeeder::class);
        $this->seed(\Database\Seeders\SampleDataSeeder::class);

        $this->assertSame(10, IncomingCommunication::count());
        $this->assertSame(10, OutgoingCommunication::count());
        $this->assertSame(5, IncomingCommunication::distinct()->count('status')); // registered, routed, in action, closed, cancelled
        $this->assertTrue(IncomingCommunication::all()->every(fn ($r) => $r->events()->exists()));
        $this->assertFalse(IncomingCommunication::where('received_at', '>', now())->exists());
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
