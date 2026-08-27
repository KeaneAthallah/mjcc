@props([
    'label' => null,
    'name' => null,
    'error' => null,
    'required' => false,
    'placeholder' => null,
    'options' => [],
    'selected' => null,
])

@php
    $hasError = $error ? true : ($errors->has($name) ? true : false);
    $errorMsg = $error ?? ($errors->first($name) ?? null);
@endphp

<div class="space-y-1">
    @if ($label)
        <label for="{{ $name }}" class="block text-[12px] font-bold text-gray-700">
            {{ $label }} @if ($required)<span class="text-red-500">*</span>@endif
        </label>
    @endif
    <select
        id="{{ $name }}"
        name="{{ $name }}"
        @if ($required) required @endif
        {{ $attributes->merge([
            'class' => 'w-full rounded-xl border text-[13px] px-3 py-2.5 bg-white focus:outline-none focus:ring-2 transition ' .
            ($hasError
                ? 'border-red-400 focus:ring-red-200 focus:border-red-400'
                : 'border-gray-300 focus:ring-emerald-200 focus:border-emerald-500')
        ]) }}
    >
        @if ($placeholder)
            <option value="">{{ $placeholder }}</option>
        @endif
        @foreach ($options as $value => $labelText)
            @php
                $isSelected = old($name) !== null
                    ? (string) old($name) === (string) $value
                    : $selected !== null && (string) $selected === (string) $value;
            @endphp
            <option value="{{ $value }}" @selected($isSelected)>{{ $labelText }}</option>
        @endforeach
    </select>
    @if ($hasError)
        <p class="text-[11px] text-red-600 font-medium mt-1">{{ $errorMsg }}</p>
    @endif
</div>
