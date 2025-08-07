@props([
    'type' => 'info',
    'dismissible' => false,
    'title' => null,
    'icon' => null
])

@php
$types = [
    'success' => [
        'container' => 'bg-green-50 border-green-200 text-green-800',
        'icon' => 'fas fa-check-circle text-green-400',
        'title' => 'text-green-800',
        'content' => 'text-green-700'
    ],
    'error' => [
        'container' => 'bg-red-50 border-red-200 text-red-800',
        'icon' => 'fas fa-exclamation-circle text-red-400',
        'title' => 'text-red-800',
        'content' => 'text-red-700'
    ],
    'warning' => [
        'container' => 'bg-yellow-50 border-yellow-200 text-yellow-800',
        'icon' => 'fas fa-exclamation-triangle text-yellow-400',
        'title' => 'text-yellow-800',
        'content' => 'text-yellow-700'
    ],
    'info' => [
        'container' => 'bg-blue-50 border-blue-200 text-blue-800',
        'icon' => 'fas fa-info-circle text-blue-400',
        'title' => 'text-blue-800',
        'content' => 'text-blue-700'
    ]
];

$config = $types[$type];
$iconClass = $icon ?? $config['icon'];
@endphp

<div {{ $attributes->merge(['class' => 'border rounded-lg p-4 ' . $config['container']]) }}
     @if($dismissible) x-data="{ show: true }" x-show="show" @endif>
    <div class="flex">
        <div class="flex-shrink-0">
            <i class="{{ $iconClass }}"></i>
        </div>
        <div class="ml-3 flex-1">
            @if($title)
                <h3 class="text-sm font-medium {{ $config['title'] }}">{{ $title }}</h3>
                <div class="mt-2 text-sm {{ $config['content'] }}">
                    {{ $slot }}
                </div>
            @else
                <div class="text-sm {{ $config['content'] }}">
                    {{ $slot }}
                </div>
            @endif
        </div>
        @if($dismissible)
            <div class="ml-auto pl-3">
                <div class="-mx-1.5 -my-1.5">
                    <button @click="show = false" 
                            type="button" 
                            class="inline-flex rounded-md p-1.5 {{ $config['content'] }} hover:bg-opacity-20 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-{{ $type }}-50 focus:ring-{{ $type }}-600">
                        <span class="sr-only">Dismiss</span>
                        <i class="fas fa-times text-sm"></i>
                    </button>
                </div>
            </div>
        @endif
    </div>
</div>