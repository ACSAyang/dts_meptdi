@props(['title'])
{{-- A secondary action, folded away until it is needed --}}
<details class="group border-t border-navy-100">
    <summary class="flex cursor-pointer list-none items-center justify-between px-5 py-3 text-sm font-semibold text-navy-800 hover:bg-navy-50/60">
        {{ $title }}
        <svg class="size-4 text-navy-400 transition group-open:rotate-180" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/></svg>
    </summary>
    <div class="px-5 pb-5">{{ $slot }}</div>
</details>
