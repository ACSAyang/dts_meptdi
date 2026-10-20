@props([
    'id',
    'title',
    'subtitle' => null,
    'size' => 'max-w-3xl',
    'open' => false,   // open on page load (e.g. to show validation errors after a failed submit)
])
{{--
    Pop-up window built on the native <dialog> element.
    Open it with any element carrying data-modal-open="{{ $id }}"; close with data-modal-close,
    the Esc key, or a click outside the window.
--}}
<dialog id="{{ $id }}" data-modal @if ($open) data-auto-open @endif
        class="modal m-auto w-[calc(100%-2rem)] {{ $size }} overflow-hidden rounded-lg bg-white p-0 text-slate-800 shadow-2xl backdrop:bg-navy-950/60 backdrop:backdrop-blur-[2px]"
        aria-labelledby="{{ $id }}-title">
    <div class="flex max-h-[90vh] flex-col">
        <div class="flex items-start justify-between gap-4 border-b border-navy-100 px-6 py-4">
            <div>
                <h2 id="{{ $id }}-title" class="text-base font-bold tracking-wide text-navy-900 uppercase">{{ $title }}</h2>
                @if ($subtitle)<p class="mt-0.5 text-[13px] text-slate-500">{{ $subtitle }}</p>@endif
            </div>
            <button type="button" data-modal-close class="-mr-2 rounded p-1.5 text-slate-400 hover:bg-navy-50 hover:text-navy-800" aria-label="Close">
                <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <div class="overflow-y-auto">{{ $slot }}</div>
    </div>
</dialog>
