<div class="mt-6 flex justify-between text-[11px] text-slate-500 print:text-black">
    <span>Generated {{ now()->format('d M Y H:i') }}H by {{ auth()->user()->displayName() }}</span>
    <span>{{ config('dts.office_code') }} DTS</span>
</div>
<div class="print-only mt-12">
    <div class="grid grid-cols-3 gap-12 text-center text-xs">
        <div><div class="border-t border-black pt-1">Prepared by</div></div>
        <div><div class="border-t border-black pt-1">Duty Office Watch</div></div>
        <div><div class="border-t border-black pt-1">Noted by</div></div>
    </div>
</div>
