@props(['unit' => 'meptdi'])
@php
    [$logo, $alt] = match ($unit) {
        'pcg' => [config('dts.service_logo'), config('dts.service').' seal'],
        'mepcom' => [config('dts.parent_logo'), config('dts.parent_command').' seal'],
        default => [
            file_exists(public_path(config('dts.logo'))) ? config('dts.logo') : config('dts.logo_placeholder'),
            'MEPTDI seal',
        ],
    };
@endphp
<img src="{{ asset($logo) }}" alt="{{ $alt }}" {{ $attributes->merge(['class' => 'object-contain']) }}>
