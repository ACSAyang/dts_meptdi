@props([
    'label', 'name', 'type' => 'text', 'value' => null, 'required' => false, 'hint' => null,
    'options' => null, 'placeholder' => null, 'rows' => 3,
    'id' => null,         // element id; defaults to the name (set it when two forms share a page)
    'bag' => 'default',   // validation error bag of the form this field belongs to
    'useOld' => true,     // refill from the previous submission (off for forms that were not submitted)
])
@php
    $id ??= $name;
    $current = $useOld ? old($name, $value) : $value;
    if ($current instanceof \DateTimeInterface) {
        $current = $type === 'date' ? $current->format('Y-m-d') : $current->format('Y-m-d\TH:i');
    }
    $error = $errors->getBag($bag)->first($name);
    $inputClass = 'input'.($error ? ' border-red-400 focus:border-red-500 focus:ring-red-500/20' : '');
    // Options may be a flat list, a value => label map, or grouped: group => [value => label].
    $grouped = is_array($options) && $options !== [] && is_array(reset($options));
    $renderOptions = function (array $list) use ($current) {
        $html = '';
        foreach ($list as $key => $optionLabel) {
            $key = array_is_list($list) ? $optionLabel : $key;
            $html .= '<option value="'.e($key).'"'.((string) $current === (string) $key ? ' selected' : '').'>'.e($optionLabel).'</option>';
        }
        return $html;
    };
@endphp
<div {{ $attributes->only('class') }}>
    <label for="{{ $id }}" class="label">{{ $label }}@if ($required) <span class="text-red-600">*</span>@endif</label>
    @if ($options !== null)
        <select id="{{ $id }}" name="{{ $name }}" class="{{ $inputClass }}" @required($required) {{ $attributes->except('class') }}>
            <option value="">{{ $required ? '— Select —' : '—' }}</option>
            @if ($grouped)
                @foreach ($options as $group => $list)
                    <optgroup label="{{ $group }}">{!! $renderOptions($list) !!}</optgroup>
                @endforeach
            @else
                {!! $renderOptions($options) !!}
            @endif
        </select>
    @elseif ($type === 'textarea')
        <textarea id="{{ $id }}" name="{{ $name }}" rows="{{ $rows }}" class="{{ $inputClass }}" placeholder="{{ $placeholder }}" @required($required) {{ $attributes->except('class') }}>{{ $current }}</textarea>
    @elseif ($type === 'password')
        <div class="relative">
            <input id="{{ $id }}" name="{{ $name }}" type="password" class="{{ $inputClass }} pr-16" placeholder="{{ $placeholder }}" @required($required) {{ $attributes->except('class') }}>
            <button type="button" data-password-toggle="{{ $id }}" class="absolute inset-y-0 right-0 px-3 text-xs font-semibold text-navy-500 hover:text-navy-800" aria-label="Show password">Show</button>
        </div>
    @else
        <input id="{{ $id }}" name="{{ $name }}" type="{{ $type }}" value="{{ $current }}" class="{{ $inputClass }}" placeholder="{{ $placeholder }}" @required($required) {{ $attributes->except('class') }}>
    @endif
    @if ($hint)<p class="hint">{{ $hint }}</p>@endif
    @if ($error)<p class="input-error">{{ $error }}</p>@endif
</div>
