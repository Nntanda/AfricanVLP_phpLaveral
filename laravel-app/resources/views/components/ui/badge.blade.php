@props([
    'variant' => 'default',
    'size' => 'md',
    'rounded' => 'default'
])

@php
$variants = [
    'default' => 'bg-gray-100 text-gray-800',
    'primary' => 'bg-blue-100 text-blue-800',
    'secondary' => 'bg-gray-100 text-gray-800',
    'success' => 'bg-green-100 text-green-800',
    'danger' => 'bg-red-100 text-red-800',
    'warning' => 'bg-yellow-100 text-yellow-800',
    'info' => 'bg-cyan-100 text-cyan-800',
    'light' => 'bg-gray-50 text-gray-600',
    'dark' => 'bg-gray-800 text-gray-100'
];

$sizes = [
    'xs' => 'px-2 py-0.5 text-xs',
    'sm' => 'px-2.5 py-0.5 text-xs',
    'md' => 'px-2.5 py-0.5 text-sm',
    'lg' => 'px-3 py-1 text-sm',
    'xl' => 'px-3 py-1 text-base'
];

$roundedClasses = [
    'none' => '',
    'sm' => 'rounded-sm',
    'default' => 'rounded',
    'md' => 'rounded-md',
    'lg' => 'rounded-lg',
    'full' => 'rounded-full'
];

$classes = 'inline-flex items-center font-medium ' . 
           $variants[$variant] . ' ' . 
           $sizes[$size] . ' ' . 
           $roundedClasses[$rounded];
@endphp

<span {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</span>