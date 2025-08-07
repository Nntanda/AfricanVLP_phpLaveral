@props([
    'title' => null,
    'subtitle' => null,
    'padding' => 'default',
    'shadow' => 'default',
    'border' => true,
    'hover' => false
])

@php
$baseClasses = 'bg-white rounded-lg';

$paddingClasses = [
    'none' => '',
    'sm' => 'p-4',
    'default' => 'p-6',
    'lg' => 'p-8'
];

$shadowClasses = [
    'none' => '',
    'sm' => 'shadow-sm',
    'default' => 'shadow',
    'lg' => 'shadow-lg',
    'xl' => 'shadow-xl'
];

$classes = $baseClasses;
$classes .= ' ' . $paddingClasses[$padding];
$classes .= ' ' . $shadowClasses[$shadow];

if ($border) {
    $classes .= ' border border-gray-200';
}

if ($hover) {
    $classes .= ' hover:shadow-lg transition-shadow duration-200';
}
@endphp

<div {{ $attributes->merge(['class' => $classes]) }}>
    @if($title || $subtitle)
        <div class="mb-4 {{ $padding === 'none' ? 'p-6 pb-0' : '' }}">
            @if($title)
                <h3 class="text-lg font-semibold text-gray-900">{{ $title }}</h3>
            @endif
            @if($subtitle)
                <p class="mt-1 text-sm text-gray-600">{{ $subtitle }}</p>
            @endif
        </div>
    @endif
    
    <div class="{{ $title || $subtitle ? ($padding === 'none' ? 'p-6 pt-0' : '') : '' }}">
        {{ $slot }}
    </div>
</div>