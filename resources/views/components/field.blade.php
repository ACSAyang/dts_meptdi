@props(['label', 'name', 'type' => 'text', 'value' => null, 'required' => false, 'hint' => null, 'options' => null, 'placeholder' => null, 'rows' => 3])
@php
    $old = old($name, $value);
    if ($old instanceof \DateTimeInterface) {
        $old = $type === 'date' ? $old->format('Y-m-d') : $old->format('Y-m-d\TH:i');
    }
@endphp
<div {{ $attributes->only('class') }}>
    <label for="{{ $name }}" class="label">{{ $label }}@if ($required) <span class="text-red-600">*</span>@endif</label>
    @if ($options !== null)
        <select id="{{ $name }}" name="{{ $name }}" class="input" @required($required) {{ $attributes->except('class') }}>
            @unless ($required)<option value="">—</option>@endunless
            @foreach ($options as $key => $optionLabel)
                @php $key = array_is_list($options) ? $optionLabel : $key; @endphp
                <option value="{{ $key }}" @selected((string) $old === (string) $key)>{{ $optionLabel }}</option>
            @endforeach
        </select>
    @elseif ($type === 'textarea')
        <textarea id="{{ $name }}" name="{{ $name }}" rows="{{ $rows }}" class="input" placeholder="{{ $placeholder }}" @required($required) {{ $attributes->except('class') }}>{{ $old }}</textarea>
    @else
        <input id="{{ $name }}" name="{{ $name }}" type="{{ $type }}" value="{{ $old }}" class="input" placeholder="{{ $placeholder }}" @required($required) {{ $attributes->except('class') }}>
    @endif
    @if ($hint)<p class="hint">{{ $hint }}</p>@endif
    @error($name)<p class="input-error">{{ $message }}</p>@enderror
</div>
