{{-- Official printed header: MEPCOM seal · PCG seal centred with the chain of command · MEPTDI seal --}}
<div {{ $attributes->merge(['class' => 'flex items-center justify-between gap-4 border-b-2 border-black pb-3 text-black']) }}>
    <x-logo unit="mepcom" class="size-[72px] shrink-0" />
    <div class="flex-1 text-center">
        <x-logo unit="pcg" class="mx-auto size-[64px]" />
        <div class="mt-2 leading-tight">
            <div class="text-[10px]">Republic of the Philippines</div>
            <div class="text-[10px]">Department of Transportation</div>
            <div class="text-[12px] font-bold uppercase">{{ config('dts.service') }}</div>
            <div class="text-[11px] font-semibold uppercase">{{ config('dts.parent_command') }}</div>
            <div class="text-[12px] font-bold uppercase">{{ config('dts.office_name') }}</div>
        </div>
    </div>
    <x-logo class="size-[72px] shrink-0" />
</div>
