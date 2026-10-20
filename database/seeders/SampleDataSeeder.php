<?php

namespace Database\Seeders;

use App\Models\AuditLog;
use App\Models\IncomingCommunication;
use App\Models\OutgoingCommunication;
use App\Models\User;
use App\Services\ReferenceNumber;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * 10 sample incoming and 10 sample outgoing communications, spread over the last
 * two weeks and at different stages, each with its full tracking history.
 *
 *   php artisan db:seed --class=SampleDataSeeder
 *
 * Runs only once (it records itself in the audit trail); every sample subject starts with "[SAMPLE]".
 */
class SampleDataSeeder extends Seeder
{
    private const MARKER = 'sample.seeded';

    public function run(): void
    {
        if (AuditLog::where('action', self::MARKER)->exists()) {
            $this->command?->warn('Sample data was already added. Nothing to do.');

            return;
        }

        $users = User::where('is_active', true)->get();
        if ($users->isEmpty()) {
            $this->command?->error('Create at least one user account first.');

            return;
        }

        DB::transaction(function () use ($users) {
            foreach ($this->incoming() as $i => $data) {
                $this->seedIncoming($data, $users->random(), now()->subDays(13 - $i)->setTime(8 + ($i % 8), 15 * ($i % 4)));
            }
            foreach ($this->outgoing() as $i => $data) {
                $this->seedOutgoing($data, $users->random(), now()->subDays(13 - $i)->setTime(9 + ($i % 7), 10 + 10 * ($i % 4)));
            }

            AuditLog::create(['action' => self::MARKER, 'description' => 'Added 10 sample incoming and 10 sample outgoing communications']);
        });

        $this->command?->info('Added 10 sample incoming and 10 sample outgoing communications.');
    }

    private function seedIncoming(array $d, User $by, Carbon $at): void
    {
        $at = $this->notFuture($at);
        $record = IncomingCommunication::create(ReferenceNumber::next(IncomingCommunication::REF_TYPE, (int) $at->format('Y')) + [
            'received_at' => $at,
            'sender_name' => $d['sender'],
            'sender_office' => $d['office'],
            'sender_reference' => $d['ref'],
            'document_date' => $at->copy()->subDays(2)->toDateString(),
            'document_type' => $d['type'],
            'subject' => '[SAMPLE] '.$d['subject'],
            'sensitivity' => $d['sensitivity'] ?? 'unclassified',
            'priority' => $d['priority'] ?? 'routine',
            'mode_of_receipt' => $d['mode'],
            'enclosures' => $d['enclosures'] ?? null,
            'requires_action' => $d['status'] !== 'registered' || isset($d['action']),
            'action_required' => $d['action'] ?? 'For appropriate action',
            'action_due_at' => $at->copy()->addDays(5)->setTime(17, 0),
            'status' => 'registered',
            'registered_by' => $by->id,
        ]);
        $record->forceFill(['created_at' => $at, 'updated_at' => $at])->saveQuietly();
        $this->event($record, 'registered', $at, $by, 'Received from '.$d['sender'].' ('.$d['office'].')');

        $status = $d['status'];
        if (in_array($status, ['routed', 'in_action', 'closed'])) {
            $routedAt = $this->notFuture($at->copy()->addHours(2));
            $record->update(['status' => 'routed', 'routed_to' => $d['route'], 'routed_at' => $routedAt]);
            $this->event($record, 'routed', $routedAt, $by, 'For appropriate action.', ['routed_to' => $d['route']]);
        }
        if (in_array($status, ['in_action', 'closed'])) {
            $updateAt = $this->notFuture($at->copy()->addDay()->setTime(10, 30));
            $record->update(['status' => 'in_action']);
            $this->event($record, 'action_update', $updateAt, $by, 'Reply being prepared by '.$d['route'].'.');
        }
        if ($status === 'closed') {
            $closedAt = $this->notFuture($at->copy()->addDays(2)->setTime(15, 0));
            $record->update(['status' => 'closed', 'action_taken' => 'Reply sent to '.$d['office'].'.', 'outcome' => 'Complied', 'closed_at' => $closedAt, 'closed_by' => $by->id]);
            $this->event($record, 'closed', $closedAt, $by, 'Outcome: Complied');
        }
        if ($status === 'cancelled') {
            $cancelledAt = $this->notFuture($at->copy()->addHour());
            $record->update(['status' => 'cancelled', 'cancelled_at' => $cancelledAt, 'cancelled_by' => $by->id, 'cancel_reason' => 'Duplicate entry']);
            $this->event($record, 'cancelled', $cancelledAt, $by, 'Reason: Duplicate entry');
        }
    }

    private function seedOutgoing(array $d, User $by, Carbon $at): void
    {
        $at = $this->notFuture($at);
        $record = OutgoingCommunication::create(ReferenceNumber::next(OutgoingCommunication::REF_TYPE, (int) $at->format('Y')) + [
            'document_date' => $at->toDateString(),
            'document_type' => $d['type'],
            'subject' => '[SAMPLE] '.$d['subject'],
            'originating_office' => $d['from'],
            'signatory' => 'CAPT Director, MEPTDI',
            'destination_office' => $d['to'],
            'addressee' => $d['addressee'],
            'sensitivity' => $d['sensitivity'] ?? 'unclassified',
            'priority' => $d['priority'] ?? 'routine',
            'mode_of_dispatch' => 'liaison',
            'liaison_name' => $d['liaison'],
            'status' => 'registered',
            'registered_by' => $by->id,
        ]);
        $record->forceFill(['created_at' => $at, 'updated_at' => $at])->saveQuietly();
        $this->event($record, 'registered', $at, $by, 'For '.$d['to'].' — '.$d['addressee']);

        $status = $d['status'];
        if (in_array($status, ['dispatched', 'follow_up', 'received', 'closed'])) {
            $out = $this->notFuture($at->copy()->addHours(1));
            $record->update(['status' => 'dispatched', 'handed_over_at' => $out, 'dispatched_at' => $out]);
            $this->event($record, 'dispatched', $out, $by, 'Handed over to '.$d['liaison'].'.');
        }
        if ($status === 'follow_up') {
            $tryAt = $this->notFuture($at->copy()->addHours(4));
            $attempt = $record->deliveryAttempts()->create([
                'attempt_no' => 1, 'attempted_at' => $tryAt, 'liaison_name' => $d['liaison'], 'result' => 'failed',
                'remarks' => 'Office closed; addressee on official travel.', 'user_id' => $by->id,
            ]);
            $record->update(['status' => 'follow_up']);
            $this->event($record, 'delivery_attempt', $tryAt, $by, 'Attempt #1: '.$attempt->resultLabel().' — Office closed; addressee on official travel.');
        }
        if (in_array($status, ['received', 'closed'])) {
            $gotAt = $this->notFuture($at->copy()->addHours(5));
            $record->update(['status' => 'received', 'received_by_name' => $d['receiver'], 'received_by_position' => 'Admin NCO, '.$d['to'], 'received_at' => $gotAt]);
            $this->event($record, 'received', $gotAt, $by, 'Received by '.$d['receiver'].'.');
        }
        if ($status === 'closed') {
            $okAt = $this->notFuture($at->copy()->addDay()->setTime(9, 0));
            $record->update(['status' => 'closed', 'verified_at' => $okAt, 'verified_by' => $by->id, 'closed_at' => $okAt, 'closed_by' => $by->id]);
            $this->event($record, 'verified', $okAt, $by, 'Receipt verified and record closed.');
        }
        if ($status === 'cancelled') {
            $cancelledAt = $this->notFuture($at->copy()->addHour());
            $record->update(['status' => 'cancelled', 'cancelled_at' => $cancelledAt, 'cancelled_by' => $by->id, 'cancel_reason' => 'Superseded by a revised letter']);
            $this->event($record, 'cancelled', $cancelledAt, $by, 'Reason: Superseded by a revised letter');
        }
    }

    private function event(Model $record, string $event, Carbon $at, User $by, string $description, array $extra = []): void
    {
        $record->events()->create($extra + [
            'event' => $event, 'description' => $description, 'occurred_at' => $at, 'user_id' => $by->id,
        ]);
    }

    private function notFuture(Carbon $at): Carbon
    {
        return $at->isFuture() ? now()->subMinutes(5) : $at;
    }

    private function incoming(): array
    {
        return [
            ['sender' => 'CO, CGD National Capital Region – Central Luzon', 'office' => 'CGDNCR-CL', 'ref' => 'CGDNCR-2026-1187', 'type' => 'Request', 'mode' => 'hand_carried', 'subject' => 'Request for training slots, Oil Spill Response Course Class 03-2026', 'route' => 'Training Division', 'status' => 'closed'],
            ['sender' => 'Commander, MEPCOM', 'office' => 'MEPCOM', 'ref' => 'MEPCOM-DIR-2026-044', 'type' => 'Directive / Order', 'mode' => 'email', 'subject' => 'Submission of 3rd Quarter Training Accomplishment Report', 'route' => 'Plans and Programs', 'status' => 'closed', 'priority' => 'priority'],
            ['sender' => 'Director, CG Education and Training Command', 'office' => 'CGETDC', 'ref' => 'CGETDC-2026-0921', 'type' => 'Memorandum', 'mode' => 'courier', 'subject' => 'Updated guidelines on the conduct of specialization courses', 'route' => 'Doctrine Division', 'status' => 'in_action', 'enclosures' => '12 pages'],
            ['sender' => 'Regional Director, DENR-EMB Region IV-A', 'office' => 'DENR-EMB IV-A', 'ref' => 'EMB4A-26-330', 'type' => 'Invitation', 'mode' => 'email', 'subject' => 'Invitation to the Coastal Clean-up and Marine Pollution Seminar', 'route' => 'Office of the Director', 'status' => 'routed'],
            ['sender' => 'CO, CG Station Batangas', 'office' => 'CGS Batangas', 'ref' => 'CGSB-2026-512', 'type' => 'Report', 'mode' => 'radio', 'subject' => 'After-activity report: oil spill response drill, Batangas Bay', 'route' => 'Operations Section', 'status' => 'closed', 'priority' => 'urgent'],
            ['sender' => 'Chief, Personnel Management Division', 'office' => 'CG-1', 'ref' => 'CG1-2026-7710', 'type' => 'Letter', 'mode' => 'courier', 'subject' => 'Nomination of instructors for the Marine Environmental Protection Officers Course', 'route' => 'Admin Section', 'status' => 'routed', 'sensitivity' => 'restricted'],
            ['sender' => 'Director, Maritime Industry Authority', 'office' => 'MARINA', 'ref' => 'MARINA-26-1044', 'type' => 'Letter', 'mode' => 'email', 'subject' => 'Request for resource speakers on MARPOL Annex I compliance', 'route' => 'Training Division', 'status' => 'in_action'],
            ['sender' => 'CO, CG Fleet', 'office' => 'CGF', 'ref' => 'CGF-2026-288', 'type' => 'Endorsement', 'mode' => 'hand_carried', 'subject' => 'Endorsement of trainees for the Basic Marine Pollution Response Course', 'route' => null, 'status' => 'registered', 'action' => 'Confirm acceptance of trainees'],
            ['sender' => 'Head, Logistics Service', 'office' => 'CG-4', 'ref' => 'CG4-2026-1302', 'type' => 'Memorandum', 'mode' => 'courier', 'subject' => 'Issuance of oil spill response equipment for training purposes', 'route' => 'Supply Section', 'status' => 'closed', 'sensitivity' => 'confidential'],
            ['sender' => 'CO, CGD Southern Tagalog', 'office' => 'CGDSTL', 'ref' => 'CGDSTL-2026-640', 'type' => 'Request', 'mode' => 'email', 'subject' => 'Request for training slots, Oil Spill Response Course (duplicate)', 'route' => null, 'status' => 'cancelled'],
        ];
    }

    private function outgoing(): array
    {
        return [
            ['type' => 'Report', 'subject' => '3rd Quarter Training Accomplishment Report', 'from' => 'Plans and Programs', 'to' => 'MEPCOM', 'addressee' => 'Commander, MEPCOM', 'liaison' => 'SN1 R Santos PCG', 'receiver' => 'PO2 M Reyes', 'status' => 'closed', 'priority' => 'priority'],
            ['type' => 'Letter', 'subject' => 'Allocation of training slots, Oil Spill Response Course Class 03-2026', 'from' => 'Training Division', 'to' => 'CGDNCR-CL', 'addressee' => 'CO, CGD NCR-CL', 'liaison' => 'ASN J Cruz PCG', 'receiver' => 'SN1 L Garcia', 'status' => 'closed'],
            ['type' => 'Memorandum', 'subject' => 'Schedule of the Marine Environmental Protection Officers Course', 'from' => 'Training Division', 'to' => 'CGETDC', 'addressee' => 'Director, CGETDC', 'liaison' => 'SN1 R Santos PCG', 'receiver' => 'PO3 A Lim', 'status' => 'received'],
            ['type' => 'Letter', 'subject' => 'Confirmation of attendance, Coastal Clean-up and Marine Pollution Seminar', 'from' => 'Office of the Director', 'to' => 'DENR-EMB IV-A', 'addressee' => 'Regional Director', 'liaison' => 'ASN J Cruz PCG', 'receiver' => 'Ms. C Bautista', 'status' => 'follow_up'],
            ['type' => 'Request', 'subject' => 'Request for issuance of training ammunition and oil-spill boom', 'from' => 'Supply Section', 'to' => 'CG-4', 'addressee' => 'Head, Logistics Service', 'liaison' => 'SN2 P Torres PCG', 'receiver' => 'PO1 D Ramos', 'status' => 'dispatched', 'sensitivity' => 'restricted'],
            ['type' => 'Endorsement', 'subject' => 'Endorsement of instructor nominees to CG-1', 'from' => 'Admin Section', 'to' => 'CG-1', 'addressee' => 'Chief, PMD', 'liaison' => 'SN1 R Santos PCG', 'receiver' => 'SN1 K Villanueva', 'status' => 'closed'],
            ['type' => 'Letter', 'subject' => 'Resource speakers for MARPOL Annex I lecture series', 'from' => 'Training Division', 'to' => 'MARINA', 'addressee' => 'Director, MARINA', 'liaison' => 'ASN J Cruz PCG', 'receiver' => 'Mr. E Navarro', 'status' => 'registered'],
            ['type' => 'Report', 'subject' => 'Post-training evaluation, Basic Marine Pollution Response Course', 'from' => 'Doctrine Division', 'to' => 'MEPCOM', 'addressee' => 'Commander, MEPCOM', 'liaison' => 'SN2 P Torres PCG', 'receiver' => 'PO2 M Reyes', 'status' => 'received'],
            ['type' => 'Certificate', 'subject' => 'Certificates of completion, Oil Spill Response Course Class 02-2026', 'from' => 'Registrar', 'to' => 'CGS Batangas', 'addressee' => 'CO, CGS Batangas', 'liaison' => 'SN1 R Santos PCG', 'receiver' => 'SN1 F Aquino', 'status' => 'closed', 'sensitivity' => 'confidential'],
            ['type' => 'Letter', 'subject' => 'Confirmation of attendance (superseded)', 'from' => 'Office of the Director', 'to' => 'DENR-EMB IV-A', 'addressee' => 'Regional Director', 'liaison' => 'ASN J Cruz PCG', 'receiver' => '-', 'status' => 'cancelled'],
        ];
    }
}
