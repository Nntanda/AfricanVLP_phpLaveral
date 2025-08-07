@props([
    'label' => null,
    'name' => null,
    'type' => 'text',
    'value' => null,
    'placeholder' => null,
    'required' => false,
    'disabled' => false,
    'readonly' => false,
    'error' => null,
    'help' => null,
    'icon' => null,
    'iconPosition' => 'left',
    'size' => 'md'
])

@php
$inputId = $name ?? 'input_' . uniqid();
$hasError = $error || $errors->has($name);
$errorMessage = $error ?? ($name ? $errors->first($name) : null);

$sizes = [
    'sm' => 'px-3 py-2 text-sm',
    'md' => 'px-3 py-2 text-sm',
    'lg' => 'px-4 py-3 text-base'
];

$baseClasses = 'block w-full border rounded-md shadow-sm focus:outline-none focus:ring-1 transition-colors duration-200';
$normalClasses = 'border-gray-300 focus:border-blue-500 focus:ring-blue-500';
$errorClasses = 'border-red-300 focus:border-red-500 focus:ring-red-500';
$disabledClasses = 'bg-gray-50 text-gray-500 cursor-not-allowed';

$inputClasses = $baseClasses . ' ' . $sizes[$size] . ' ';
$inputClasses .= $hasError ? $errorClasses : $normalClasses;
$inputClasses .= $disabled ? ' ' . $disabledClasses : '';
$inputClasses .= $icon ? ($iconPosition === 'left' ? ' pl-10' : ' pr-10') : '';
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

    <div class="relative">
        @if($icon)
            <div class="absolute inset-y-0 {{ $iconPosition === 'left' ? 'left-0 pl-3' : 'right-0 pr-3' }} flex items-center pointer-events-none">
                <i class="{{ $icon }} text-gray-400"></i>
            </div>
        @endif

        <input
            type="{{ $type }}"
            id="{{ $inputId }}"
            name="{{ $name }}"
            value="{{ old($name, $value) }}"
            placeholder="{{ $placeholder }}"
            class="{{ $inputClasses }}"
            @if($required) required @endif
            @if($disabled) disabled @endif
            @if($readonly) readonly @endif
            {{ $attributes->except(['class', 'label', 'name', 'type', 'value', 'placeholder', 'required', 'disabled', 'readonly', 'error', 'help', 'icon', 'iconPosition', 'size']) }}
        >
    </div>

    @if($help && !$hasError)
        <p class="mt-1 text-sm text-gray-500">{{ $help }}</p>
    @endif

    @if($hasError)
        <p class="mt-1 text-sm text-red-600">{{ $errorMessage }}</p>
    @endif
</div>