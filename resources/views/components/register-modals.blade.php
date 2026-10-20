{{--
    "Register incoming" and "Register outgoing" pop-ups, available on every page.
    Any element with data-modal-open="modal-incoming" / "modal-outgoing" opens them.
    After a failed submission the page comes back with the pop-up open and the errors shown.
--}}
@php
    use App\Models\DutyPeriod;
    use App\Models\IncomingCommunication;
    use App\Models\OutgoingCommunication;
    use App\Services\ReferenceNumber;

    $onDuty = DutyPeriod::current();
    $forms = [
        'incoming' => [
            'title' => 'Register incoming communication',
            'route' => route('incoming.store'),
            'record' => new IncomingCommunication(['received_at' => now(), 'sensitivity' => 'unclassified', 'priority' => 'routine']),
            'next' => $onDuty ? ReferenceNumber::preview(IncomingCommunication::REF_TYPE) : null,
        ],
        'outgoing' => [
            'title' => 'Register outgoing communication',
            'route' => route('outgoing.store'),
            'record' => new OutgoingCommunication(['document_date' => now(), 'sensitivity' => 'unclassified', 'priority' => 'routine', 'mode_of_dispatch' => 'liaison']),
            'next' => $onDuty ? ReferenceNumber::preview(OutgoingCommunication::REF_TYPE) : null,
        ],
    ];
@endphp

@foreach ($forms as $type => $form)
    @php $failed = $errors->getBag($type)->any(); @endphp
    <x-modal :id="'modal-'.$type" :title="$form['title']" size="max-w-4xl" :open="$failed"
             :subtitle="$onDuty ? 'Reference number to be issued: '.$form['next'].' · DOW '.$onDuty->user->displayName() : null">
        @if ($onDuty)
            <form method="POST" action="{{ $form['route'] }}" enctype="multipart/form-data">
                @csrf
                <div class="p-6">
                    @if ($failed)
                        <div class="mb-5 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">Please correct the highlighted fields.</div>
                    @endif
                    @include($type.'._fields', ['record' => $form['record'], 'p' => 'm'.$type[0].'-', 'bag' => $type, 'useOld' => $failed])
                </div>
                <div class="sticky bottom-0 flex justify-end gap-2 border-t border-navy-100 bg-navy-50 px-6 py-4">
                    <button type="button" data-modal-close class="btn btn-secondary">Cancel</button>
                    <button type="submit" class="btn btn-accent">Register</button>
                </div>
            </form>
        @else
            <div class="px-6 py-10 text-center">
                <div class="mx-auto flex size-12 items-center justify-center rounded-full bg-accent-500/10 text-accent-600"><x-icon name="shield" class="size-6" /></div>
                <div class="mt-3 font-semibold text-navy-900">No one is on duty</div>
                <p class="mt-1 text-sm text-slate-500">Someone must start duty before communications can be registered.</p>
                <a href="{{ route('duty.index') }}" class="btn btn-accent mt-5">Go to Duty Office Watch</a>
            </div>
        @endif
    </x-modal>
@endforeach
