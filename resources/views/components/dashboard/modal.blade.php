@props([
    'id',
    'titleId',
    'contentId',
    'closeFunction',
    'linkHref' => '#',
    'linkText' => 'Ver más →',
    'linkId' => null,
    'linkColor' => 'blue'
])

@php
    $linkColors = [
        'blue' => 'text-blue-600 hover:text-blue-800 dark:text-blue-400',
        'green' => 'text-green-600 hover:text-green-800 dark:text-green-400',
    ];
@endphp

<div id="{{ $id }}" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white dark:bg-gray-800">
        <div class="flex justify-between items-center mb-4">
            <h3 id="{{ $titleId }}" class="text-lg font-medium text-gray-900 dark:text-gray-100"></h3>
            <button onclick="{{ $closeFunction }}()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewB
               ox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        <div id="{{ $contentId }}" class="space-y-3 max-h-80 overflow-y-auto">
            {{-- Contenido dinámico --}}
        </div>
        <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
            <a @if($linkId) id="{{ $linkId }}" @endif href="{{ $linkHref }}" class="text-sm {{ $linkColors[$linkColor] ?? $linkColors['blue'] }}">
                {{ $linkText }}
            </a>
        </div>
    </div>
</div>
