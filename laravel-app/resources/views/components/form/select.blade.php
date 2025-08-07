@props([
    'label' => null,
    'name' => null,
    'value' => null,
    'placeholder' => null,
    'required' => false,
    'disabled' => false,
    'error' => null,
    'help' => null,
    'options' => [],
    'size' => 'md'
])

@php
$inputId = $name ?? 'select_' . uniqid();
$hasError = $error || $errors->has($name);
$errorMessage = $error ?? ($name ? $errors->first($name) : null);

$sizes = [
    'sm' => 'px-3 py-2 text-sm',
    'md' => 'px-3 py-2 text-sm',
    'lg' => 'px-4 py-3 text-base'
];

$baseClasses = 'block w-full border rounded-md shadow-sm focus:outline-none focus:ring-1 transition-colors duration-200 bg-white';
$normalClasses = 'border-gray-300 focus:border-blue-500 focus:ring-blue-500';
$errorClasses = 'border-red-300 focus:border-red-500 focus:ring-red-500';
$disabledClasses = 'bg-gray-50 text-gray-500 cursor-not-allowed';

$selectClasses = $baseClasses . ' ' . $sizes[$size] . ' ';
$selectClasses .= $hasError ? $errorClasses : $normalClasses;
$selectClasses .= $disabled ? ' ' . $disabledClasses : '';
@endphp

<div {{ $attributes->only('class') }}>
    @if($label)
        <label for="{{ $inputId }}" class="block text-sm font-medium text-gray-700 mb-1">
            {{ $label }}
            @if($required)
                <span class="text-red-500">*</span>
            @endif
        </label>
    @endif

    <select
        id="{{ $inputId }}"
        name="{{ $name }}"
        class="{{ $selectClasses }}"
        @if($required) required @endif
        @if($disabled) disabled @endif
        {{ $attributes->except(['class', 'label', 'name', 'value', 'placeholder', 'required', 'disabled', 'error', 'help', 'options', 'size']) }}
    >
        @if($placeholder)
            <option value="">{{ $placeholder }}</option>
        @endif

        @if(is_array($options) || is_object($options))
            @foreach($options as $optionValue => $optionLabel)
                <option value="{{ $optionValue }}" 
                        @if(old($name, $value) == $optionValue) selected @endif>
                    {{ $optionLabel }}
                </option>
            @endforeach
        @else
            {{ $slot }}
        @endif
    </select>

    @if($help && !$hasError)
        <p class="mt-1 text-sm text-gray-500">{{ $help }}</p>
    @endif

    @if($hasError)
        <p class="mt-1 text-sm text-red-600">{{ $errorMessage }}</p>
    @endif
</div>