@props([
    'icon',
    'iconBg' => 'blue',
    'label',
    'value',
    'colspan' => 1
])

@php
    $bgColors = [
        'blue' => 'bg-blue-100 dark:bg-blue-800',
        'green' => 'bg-green-100 dark:bg-green-800',
        'purple' => 'bg-purple-100 dark:bg-purple-800',
        'orange' => 'bg-orange-100 dark:bg-orange-800',
    ];
    
    $iconColors = [
        'blue' => 'text-blue-600 dark:text-blue-300',
        'green' => 'text-green-600 dark:text-green-300',
        'purple' => 'text-purple-600 dark:text-purple-300',
        'orange' => 'text-orange-600 dark:text-orange-300',
    ];
@endphp

<div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-4 {{ $colspan > 1 ? 'col-span-' . $colspan : '' }}">
    <div class="flex items-center">
        <div class="p-3 rounded-full {{ $bgColors[$iconBg] ?? $bgColors['blue'] }} mr-3">
            @switch($icon)
                @case('home')
                    <svg class="w-5 h-5 {{ $iconColors[$iconBg] ?? $iconColors['blue'] }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6">
                        </path>
                    </svg>
                    @break
                @case('clock')
                    <svg class="w-5 h-5 {{ $iconColors[$iconBg] ?? $iconColors['blue'] }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    @break
                @case('users')
                    <svg class="w-5 h-5 {{ $iconColors[$iconBg] ?? $iconColors['blue'] }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z">
                        </path>
                    </svg>
                    @break
                @case('clipboard')
                    <svg class="w-5 h-5 {{ $iconColors[$iconBg] ?? $iconColors['blue'] }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2">
                        </path>
                    </svg>
                    @break
                @case('calendar')
                    <svg class="w-5 h-5 {{ $iconColors[$iconBg] ?? $iconColors['blue'] }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z">
                        </path>
                    </svg>
                    @break
            @endswitch
        </div>
        <div>
            <p class="text-xs text-gray-500 dark:text-gray-400">{{ $label }}</p>
            <p class="text-xl font-bold text-gray-900 dark:text-gray-100">{{ $value }}</p>
        </div>
    </div>
</div>
