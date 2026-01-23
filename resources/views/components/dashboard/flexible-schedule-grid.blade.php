@props(['currentYear', 'currentMonth', 'flexibleAssignments'])

@php
    $groupedByArea = $flexibleAssignments->groupBy(fn($a) => $a->user->work_area)->sortKeys();
@endphp

<div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg mb-6">
    <div class="p-6">
        {{-- Header --}}
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 flex items-center">
                <x-icons.time class="w-5 h-5 mr-2" /> Horarios Flexibles -
                {{ Carbon\Carbon::create($currentYear, $currentMonth, 1)->locale('es')->monthName }}
            </h3>
            <a href="{{ route('flexible-schedule.index') }}"
                class="text-sm text-green-600 hover:text-green-800 dark:text-green-400 inline-flex items-center">
                Gestionar horarios <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
            </a>
        </div>

        @if($flexibleAssignments->count() > 0)
            {{-- Leyenda --}}
            <div class="flex flex-wrap gap-4 mb-4 text-sm">
                <div class="flex items-center">
                    <span class="w-3 h-3 bg-green-500 rounded-full mr-2"></span>
                    <span class="text-gray-600 dark:text-gray-400">08:00</span>
                </div>
                <div class="flex items-center">
                    <span class="w-3 h-3 bg-yellow-500 rounded-full mr-2"></span>
                    <span class="text-gray-600 dark:text-gray-400">08:30</span>
                </div>
                <div class="flex items-center">
                    <span class="w-3 h-3 bg-blue-500 rounded-full mr-2"></span>
                    <span class="text-gray-600 dark:text-gray-400">09:00</span>
                </div>
                <div class="flex items-center">
                    <span class="w-3 h-3 bg-purple-500 rounded-full mr-2"></span>
                    <span class="text-gray-600 dark:text-gray-400">Otros</span>
                </div>
            </div>

            {{-- Grid de áreas --}}
            <div class="flex flex-wrap gap-2">
                @foreach($groupedByArea as $area => $areaAssignments)
                    <div onclick="openFlexibleModal('{{ $area }}')"
                        class="w-[calc(14.28%-0.5rem)] min-w-[120px] h-20 p-2 rounded-lg border-2 border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 cursor-pointer hover:bg-green-50 dark:hover:bg-green-900/30 hover:border-green-400 dark:hover:border-green-500 transition-all overflow-hidden">
                        <div class="flex justify-between items-start">
                            <span class="text-sm font-bold text-gray-700 dark:text-gray-300 truncate" title="{{ $area }}">
                                {{ Str::limit($area, 12) }}
                            </span>
                            <span
                                class="flex items-center justify-center w-5 h-5 text-xs font-bold text-white bg-green-500 rounded-full shadow-sm">
                                {{ $areaAssignments->count() }}
                            </span>
                        </div>
                        <div class="mt-1 space-y-0.5">
                            @foreach($areaAssignments->take(2) as $assignment)
                                <p class="text-xs text-green-600 dark:text-green-400 truncate leading-tight">
                                    {{ Str::before($assignment->user->name, ' ') }}
                                </p>
                            @endforeach
                            @if($areaAssignments->count() > 2)
                                <p class="text-xs text-gray-500 dark:text-gray-400">+{{ $areaAssignments->count() - 2 }} más</p>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Resumen total --}}
            <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    <span class="font-semibold">Total:</span> {{ $flexibleAssignments->count() }} persona(s) con horario
                    flexible en {{ $groupedByArea->count() }} área(s)
                </p>
            </div>
        @else
            <p class="text-gray-500 dark:text-gray-400">No hay asignaciones de horario flexible este mes.</p>
        @endif
    </div>
</div>