{{--
    Outgoing communication fields, shared by the full page and the pop-up.
    $record, $p (id prefix, unique per page), $bag (error bag), $useOld (refill from last submit)
--}}
@php
    $p ??= '';
    $bag ??= 'default';
    $useOld ??= true;
@endphp
<div class="space-y-6">
    <fieldset>
        <legend class="card-title mb-3">Document</legend>
        <div class="grid gap-4 md:grid-cols-3">
            <x-field label="Document type" name="document_type" :id="$p.'document_type'" :options="config('dts.document_types')" :value="$record->document_type" required :bag="$bag" :use-old="$useOld" />
            <x-field label="Document date" name="document_date" :id="$p.'document_date'" type="date" :value="$record->document_date" :bag="$bag" :use-old="$useOld" />
            <x-field label="Enclosures / pages" name="enclosures" :id="$p.'enclosures'" :value="$record->enclosures" :bag="$bag" :use-old="$useOld" />
            <x-field label="Subject" name="subject" :id="$p.'subject'" type="textarea" rows="2" :value="$record->subject" required class="md:col-span-3" :bag="$bag" :use-old="$useOld" />
            <x-field label="Originating office / section" name="originating_office" :id="$p.'originating_office'" :value="$record->originating_office" :bag="$bag" :use-old="$useOld" />
            <x-field label="Signatory" name="signatory" :id="$p.'signatory'" :value="$record->signatory" :bag="$bag" :use-old="$useOld" />
            <div class="hidden md:block"></div>
            <x-field label="Sensitivity" name="sensitivity" :id="$p.'sensitivity'" :options="config('dts.sensitivities')" :value="$record->sensitivity" required
                     hint="Handling label. Do not encode Secret or higher." :bag="$bag" :use-old="$useOld" />
            <x-field label="Priority" name="priority" :id="$p.'priority'" :options="config('dts.priorities')" :value="$record->priority" required :bag="$bag" :use-old="$useOld" />
        </div>
    </fieldset>

    <fieldset>
        <legend class="card-title mb-3">Destination &amp; dispatch</legend>
        <div class="grid gap-4 md:grid-cols-3">
            <x-field label="Destination office / unit" name="destination_office" :id="$p.'destination_office'" :value="$record->destination_office" required :bag="$bag" :use-old="$useOld" />
            <x-field label="Addressee" name="addressee" :id="$p.'addressee'" :value="$record->addressee" :bag="$bag" :use-old="$useOld" />
            <x-field label="Mode of dispatch" name="mode_of_dispatch" :id="$p.'mode_of_dispatch'" :options="config('dts.dispatch_modes')" :value="$record->mode_of_dispatch" required :bag="$bag" :use-old="$useOld" />
            <x-field label="Destination address" name="destination_address" :id="$p.'destination_address'" type="textarea" rows="2" :value="$record->destination_address" class="md:col-span-2" :bag="$bag" :use-old="$useOld" />
            <x-field label="Assigned liaison" name="liaison_name" :id="$p.'liaison_name'" :value="$record->liaison_name" hint="Needed before dispatch when hand-carried." :bag="$bag" :use-old="$useOld" />
            <x-field label="Remarks" name="remarks" :id="$p.'remarks'" type="textarea" rows="2" :value="$record->remarks" class="md:col-span-3" :bag="$bag" :use-old="$useOld" />
        </div>
    </fieldset>

    @unless ($record->exists)
        <fieldset>
            <legend class="card-title mb-3">Attachments <span class="font-medium text-slate-400 normal-case">(optional)</span></legend>
            <input type="file" name="attachments[]" multiple class="input file:mr-3 file:rounded file:border-0 file:bg-navy-100 file:px-3 file:py-1 file:text-xs file:font-medium file:text-navy-800">
            <p class="hint">Copy of the outgoing document. PDF, images, Word or Excel; up to 20 MB each.</p>
            @if ($e = $errors->getBag($bag)->first('attachments.*'))<p class="input-error">{{ $e }}</p>@endif
        </fieldset>
    @endunless
</div>
