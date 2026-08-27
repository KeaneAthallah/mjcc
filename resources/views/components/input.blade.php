@props([
    'label' => null,
    'name' => null,
    'error' => null,
    'type' => 'text',
    'placeholder' => null,
    'required' => false,
    'help' => null,
    'value' => null,
    'step' => null,
    'min' => null,
    'max' => null,
    'inputmode' => null,
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
    <input
        id="{{ $name }}"
        name="{{ $name }}"
        type="{{ $type }}"
        value="{{ old($name, $value) }}"
        @if ($placeholder) placeholder="{{ $placeholder }}" @endif
        @if ($required) required @endif
        @if ($step !== null) step="{{ $step }}" @endif
        @if ($min !== null) min="{{ $min }}" @endif
        @if ($max !== null) max="{{ $max }}" @endif
        @if ($inputmode) inputmode="{{ $inputmode }}" @endif
        {{ $attributes->merge([
            'class' => 'w-full rounded-xl border text-[13px] px-3.5 py-2.5 bg-white focus:outline-none focus:ring-2 transition ' .
            ($hasError
                ? 'border-red-400 focus:ring-red-200 focus:border-red-400'
                : 'border-gray-300 focus:ring-emerald-200 focus:border-emerald-500')
        ]) }}
    >
    @if ($hasError)
        <p class="text-[11px] text-red-600 font-medium mt-1">{{ $errorMsg }}</p>
    @endif
    @if ($help)
        <p class="text-[11px] text-gray-400">{{ $help }}</p>
    @endif
</div>
