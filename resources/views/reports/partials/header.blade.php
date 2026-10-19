{{-- $heading, $subheading, optional $filters slot via $filtersView --}}
<div class="mb-5 flex flex-wrap items-end justify-between gap-3">
    <div>
        <a href="{{ route('reports.index') }}" class="no-print mb-1 inline-flex items-center gap-1 text-xs text-navy-600 hover:text-navy-900"><x-icon name="arrow-left" class="size-3" /> Reports</a>
        <h1 class="text-xl font-semibold text-navy-900 print:text-center print:text-base print:uppercase">{{ $heading }}</h1>
        <p class="text-sm text-slate-500 print:text-center print:text-xs print:text-black">{{ $subheading }}</p>
    </div>
    <button data-print class="no-print btn btn-secondary"><x-icon name="print" /> Print</button>
</div>
