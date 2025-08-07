@props([
    'label' => null,
    'name' => null,
    'value' => null,
    'placeholder' => null,
    'required' => false,
    'disabled' => false,
    'readonly' => false,
    'error' => null,
    'help' => null,
    'rows' => 4,
    'size' => 'md'
])

@php
$inputId = $name ?? 'textarea_' . uniqid();
$hasError = $error || $errors->has($name);
$errorMessage = $error ?? ($name ? $errors->first($name) : null);

$sizes = [
    'sm' => 'px-3 py-2 text-sm',
    'md' => 'px-3 py-2 text-sm',
    'lg' => 'px-4 py-3 text-base'
];

$baseClasses = 'block w-full border rounded-md shadow-sm focus:outline-none focus:ring-1 transition-colors duration-200 resize-vertical';
$normalClasses = 'border-gray-300 focus:border-blue-500 focus:ring-blue-500';
$errorClasses = 'border-red-300 focus:border-red-500 focus:ring-red-500';
$disabledClasses = 'bg-gray-50 text-gray-500 cursor-not-allowed';

$textareaClasses = $baseClasses . ' ' . $sizes[$size] . ' ';
$textareaClasses .= $hasError ? $errorClasses : $normalClasses;
$textareaClasses .= $disabled ? ' ' . $disabledClasses : '';
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

    <textarea
        id="{{ $inputId }}"
        name="{{ $name }}"
        rows="{{ $rows }}"
        placeholder="{{ $placeholder }}"
        class="{{ $textareaClasses }}"
        @if($required) required @endif
        @if($disabled) disabled @endif
        @if($readonly) readonly @endif
        {{ $attributes->except(['class', 'label', 'name', 'value', 'placeholder', 'required', 'disabled', 'readonly', 'error', 'help', 'rows', 'size']) }}
    >{{ old($name, $value) }}</textarea>

    @if($help && !$hasError)
        <p class="mt-1 text-sm text-gray-500">{{ $help }}</p>
    @endif

    @if($hasError)
        <p class="mt-1 text-sm text-red-600">{{ $errorMessage }}</p>
    @endif
</div>