{{--
    Incoming communication fields, shared by the full page and the pop-up.
    $record, $p (id prefix, unique per page), $bag (error bag), $useOld (refill from last submit)
--}}
@php
    $p ??= '';
    $bag ??= 'default';
    $useOld ??= true;
    $requiresAction = $useOld ? old('requires_action', $record->requires_action) : $record->requires_action;
@endphp
<div class="space-y-6">
    <fieldset>
        <legend class="card-title mb-3">Receipt</legend>
        <div class="grid gap-4 md:grid-cols-3">
            <x-field label="Date / time received" name="received_at" :id="$p.'received_at'" type="datetime-local" :value="$record->received_at" required :bag="$bag" :use-old="$useOld" />
            <x-field label="Mode of receipt" name="mode_of_receipt" :id="$p.'mode_of_receipt'" :options="config('dts.receipt_modes')" :value="$record->mode_of_receipt" :bag="$bag" :use-old="$useOld" />
            <x-field label="Enclosures / pages" name="enclosures" :id="$p.'enclosures'" :value="$record->enclosures" placeholder="e.g. 3 pages, 1 annex" :bag="$bag" :use-old="$useOld" />
            <x-field label="Sender (name / position)" name="sender_name" :id="$p.'sender_name'" :value="$record->sender_name" required class="md:col-span-2" :bag="$bag" :use-old="$useOld" />
            <x-field label="Sender office / unit" name="sender_office" :id="$p.'sender_office'" :value="$record->sender_office" :bag="$bag" :use-old="$useOld" />
        </div>
    </fieldset>

    <fieldset>
        <legend class="card-title mb-3">Document</legend>
        <div class="grid gap-4 md:grid-cols-3">
            <x-field label="Document type" name="document_type" :id="$p.'document_type'" :options="config('dts.document_types')" :value="$record->document_type" required :bag="$bag" :use-old="$useOld" />
            <x-field label="Sender's reference no." name="sender_reference" :id="$p.'sender_reference'" :value="$record->sender_reference" :bag="$bag" :use-old="$useOld" />
            <x-field label="Document date" name="document_date" :id="$p.'document_date'" type="date" :value="$record->document_date" :bag="$bag" :use-old="$useOld" />
            <x-field label="Subject" name="subject" :id="$p.'subject'" type="textarea" rows="2" :value="$record->subject" required class="md:col-span-3" :bag="$bag" :use-old="$useOld" />
            <x-field label="Sensitivity" name="sensitivity" :id="$p.'sensitivity'" :options="config('dts.sensitivities')" :value="$record->sensitivity" required
                     hint="Handling label. Do not encode Secret or higher." :bag="$bag" :use-old="$useOld" />
            <x-field label="Priority" name="priority" :id="$p.'priority'" :options="config('dts.priorities')" :value="$record->priority" required :bag="$bag" :use-old="$useOld" />
        </div>
    </fieldset>

    <fieldset>
        <legend class="card-title mb-3">Handling</legend>
        <div class="grid gap-4 md:grid-cols-3">
            <x-field label="Handling remarks" name="handling_remarks" :id="$p.'handling_remarks'" type="textarea" rows="2" :value="$record->handling_remarks" class="md:col-span-3" :bag="$bag" :use-old="$useOld" />
            <label class="flex items-center gap-2 text-sm font-medium text-navy-900 md:col-span-3">
                <input type="checkbox" name="requires_action" value="1" data-toggle="#{{ $p }}action-fields" class="rounded border-navy-300" @checked($requiresAction)>
                This communication requires action / compliance
            </label>
            <div id="{{ $p }}action-fields" class="grid gap-4 md:col-span-3 md:grid-cols-3">
                <x-field label="Action required" name="action_required" :id="$p.'action_required'" type="textarea" rows="2" :value="$record->action_required" class="md:col-span-2" :bag="$bag" :use-old="$useOld" />
                <x-field label="Action due" name="action_due_at" :id="$p.'action_due_at'" type="datetime-local" :value="$record->action_due_at" :bag="$bag" :use-old="$useOld" />
            </div>
        </div>
    </fieldset>

    @unless ($record->exists)
        <fieldset>
            <legend class="card-title mb-3">Attachments <span class="font-medium text-slate-400 normal-case">(optional)</span></legend>
            <input type="file" name="attachments[]" multiple class="input file:mr-3 file:rounded file:border-0 file:bg-navy-100 file:px-3 file:py-1 file:text-xs file:font-medium file:text-navy-800">
            <p class="hint">Scanned copy of the document. PDF, images, Word or Excel; up to 20 MB each.</p>
            @if ($e = $errors->getBag($bag)->first('attachments.*'))<p class="input-error">{{ $e }}</p>@endif
        </fieldset>
    @endunless
</div>
