<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Separate yearly sequences for incoming (IN) and outgoing (OUT) reference numbers.
        Schema::create('reference_sequences', function (Blueprint $table) {
            $table->id();
            $table->string('type', 10);
            $table->unsignedSmallInteger('year');
            $table->unsignedInteger('last_number')->default(0);
            $table->timestamps();
            $table->unique(['type', 'year']);
        });

        Schema::create('duty_periods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('assigned_by')->nullable()->constrained('users');
            $table->dateTime('scheduled_start');
            $table->dateTime('scheduled_end');
            $table->dateTime('started_at')->nullable();
            $table->dateTime('ended_at')->nullable();
            // scheduled | active | completed | cancelled
            $table->string('status', 20)->default('scheduled')->index();
            $table->text('assignment_remarks')->nullable();
            $table->text('cancel_reason')->nullable();
            $table->timestamps();
        });

        Schema::create('duty_turnovers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('from_duty_period_id')->constrained('duty_periods');
            $table->foreignId('from_user_id')->constrained('users');
            $table->foreignId('to_duty_period_id')->nullable()->constrained('duty_periods');
            $table->foreignId('to_user_id')->nullable()->constrained('users');
            $table->dateTime('turned_over_at');
            $table->text('notes')->nullable();
            $table->json('pending_snapshot')->nullable();
            $table->dateTime('acknowledged_at')->nullable();
            $table->text('acknowledgment_remarks')->nullable();
            $table->timestamps();
        });

        Schema::create('incoming_communications', function (Blueprint $table) {
            $table->id();
            $table->string('reference_no', 40)->unique();
            $table->unsignedSmallInteger('ref_year');
            $table->unsignedInteger('ref_sequence');
            $table->dateTime('received_at')->index();
            $table->string('sender_name');
            $table->string('sender_office')->nullable();
            $table->string('sender_reference')->nullable();
            $table->date('document_date')->nullable();
            $table->string('document_type', 50);
            $table->text('subject');
            $table->string('sensitivity', 20)->default('unclassified')->index();
            $table->string('priority', 20)->default('routine');
            $table->string('mode_of_receipt', 30)->nullable();
            $table->string('enclosures')->nullable();
            $table->text('handling_remarks')->nullable();
            $table->boolean('requires_action')->default(false);
            $table->text('action_required')->nullable();
            $table->dateTime('action_due_at')->nullable();
            $table->string('routed_to')->nullable();
            $table->dateTime('routed_at')->nullable();
            // registered | routed | in_action | closed | cancelled
            $table->string('status', 20)->default('registered')->index();
            $table->text('action_taken')->nullable();
            $table->text('outcome')->nullable();
            $table->dateTime('closed_at')->nullable();
            $table->foreignId('closed_by')->nullable()->constrained('users');
            $table->dateTime('cancelled_at')->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users');
            $table->text('cancel_reason')->nullable();
            $table->foreignId('registered_by')->constrained('users');
            $table->foreignId('duty_period_id')->nullable()->constrained('duty_periods');
            $table->timestamps();
            $table->unique(['ref_year', 'ref_sequence']);
        });

        Schema::create('outgoing_communications', function (Blueprint $table) {
            $table->id();
            $table->string('reference_no', 40)->unique();
            $table->unsignedSmallInteger('ref_year');
            $table->unsignedInteger('ref_sequence');
            $table->date('document_date')->nullable();
            $table->string('document_type', 50);
            $table->text('subject');
            $table->string('originating_office')->nullable();
            $table->string('signatory')->nullable();
            $table->string('destination_office');
            $table->string('addressee')->nullable();
            $table->text('destination_address')->nullable();
            $table->string('sensitivity', 20)->default('unclassified')->index();
            $table->string('priority', 20)->default('routine');
            $table->string('mode_of_dispatch', 30)->nullable();
            $table->string('enclosures')->nullable();
            $table->text('remarks')->nullable();
            $table->string('liaison_name')->nullable();
            $table->dateTime('dispatched_at')->nullable();
            $table->dateTime('handed_over_at')->nullable();
            $table->text('handover_remarks')->nullable();
            // registered | dispatched | follow_up | received | closed | cancelled
            $table->string('status', 20)->default('registered')->index();
            $table->string('received_by_name')->nullable();
            $table->string('received_by_position')->nullable();
            $table->dateTime('received_at')->nullable();
            $table->text('receipt_remarks')->nullable();
            $table->dateTime('verified_at')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users');
            $table->text('verification_remarks')->nullable();
            $table->dateTime('closed_at')->nullable();
            $table->foreignId('closed_by')->nullable()->constrained('users');
            $table->dateTime('cancelled_at')->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users');
            $table->text('cancel_reason')->nullable();
            $table->foreignId('registered_by')->constrained('users');
            $table->foreignId('duty_period_id')->nullable()->constrained('duty_periods');
            $table->timestamps();
            $table->unique(['ref_year', 'ref_sequence']);
        });

        Schema::create('delivery_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('outgoing_communication_id')->constrained('outgoing_communications');
            $table->unsignedSmallInteger('attempt_no');
            $table->dateTime('attempted_at');
            $table->string('liaison_name')->nullable();
            // delivered | failed | refused | rescheduled
            $table->string('result', 20);
            $table->text('remarks')->nullable();
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('duty_period_id')->nullable()->constrained('duty_periods');
            $table->timestamps();
        });

        // Timeline of every step taken on a record (routing, actions, dispatch, delivery, closure...).
        Schema::create('record_events', function (Blueprint $table) {
            $table->id();
            $table->morphs('trackable');
            $table->string('event', 40);
            $table->text('description')->nullable();
            $table->string('routed_to')->nullable();
            $table->json('meta')->nullable();
            $table->dateTime('occurred_at');
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('duty_period_id')->nullable()->constrained('duty_periods');
            $table->timestamps();
        });

        Schema::create('attachments', function (Blueprint $table) {
            $table->id();
            $table->morphs('attachable');
            // document | proof_of_receipt | acknowledgment | other
            $table->string('category', 30)->default('document');
            $table->string('original_name');
            $table->string('path');
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('size')->default(0);
            $table->string('description')->nullable();
            $table->foreignId('uploaded_by')->constrained('users');
            $table->foreignId('duty_period_id')->nullable()->constrained('duty_periods');
            $table->timestamps();
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users');
            $table->foreignId('duty_period_id')->nullable()->constrained('duty_periods');
            $table->string('action', 50)->index();
            $table->nullableMorphs('auditable');
            $table->string('reference_no', 40)->nullable()->index();
            $table->text('description')->nullable();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->text('reason')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamp('created_at')->useCurrent()->index();
        });

        Schema::create('backup_logs', function (Blueprint $table) {
            $table->id();
            $table->string('type', 20); // backup | restore_test
            $table->string('status', 20); // success | failed
            $table->string('file_path')->nullable();
            $table->unsignedBigInteger('size')->nullable();
            $table->text('details')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('backup_logs');
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('attachments');
        Schema::dropIfExists('record_events');
        Schema::dropIfExists('delivery_attempts');
        Schema::dropIfExists('outgoing_communications');
        Schema::dropIfExists('incoming_communications');
        Schema::dropIfExists('duty_turnovers');
        Schema::dropIfExists('duty_periods');
        Schema::dropIfExists('reference_sequences');
    }
};
