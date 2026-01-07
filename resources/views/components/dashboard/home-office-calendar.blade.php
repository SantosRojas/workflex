@props(['user', 'currentYear', 'currentMonth', 'homeOfficeAssignments'])

@php
    $firstDay = Carbon\Carbon::create($currentYear, $currentMonth, 1);
    $lastDay = $firstDay->copy()->endOfMonth();
    $startPadding = $firstDay->dayOfWeekIso - 1;
    $assignmentsByDate = $homeOfficeAssignments->groupBy(fn($a) => $a->date->format('Y-m-d'));

    // Calcular mes anterior y siguiente
    $previousMonth = $firstDay->copy()->subMonth();
    $nextMonth = $firstDay->copy()->addMonth();
@endphp

<div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg mb-6">
    <div class="p-6">
        {{-- Header --}}
        <div class="flex justify-between items-center mb-4">
            <div class="flex items-center gap-2">
                {{-- Botones de navegación (solo para managers/admin) --}}
                @if($user->canManageAssignments())
                    <a href="{{ route('dashboard', ['month' => $previousMonth->month, 'year' => $previousMonth->year]) }}"
                        class="inline-flex items-center justify-center w-8 h-8 rounded-md bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors"
                        title="Mes anterior">
                        ←
                    </a>
                @endif

                <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 min-w-max">
                    📅 Calendario -
                    {{ Carbon\Carbon::create($currentYear, $currentMonth, 1)->locale('es')->monthName }}
                    {{ $currentYear }}
                </h3>

                {{-- Botones de navegación (solo para managers/admin) --}}
                @if($user->canManageAssignments())
                    <a href="{{ route('dashboard', ['month' => $nextMonth->month, 'year' => $nextMonth->year]) }}"
                        class="inline-flex items-center justify-center w-8 h-8 rounded-md bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors"
                        title="Mes siguiente">
                        →
                    </a>
                @endif
            </div>

            @if($user->canManageAssignments())
                <a href="{{ route('home-office.index') }}"
                    class="text-sm text-blue-600 hover:text-blue-800 dark:text-blue-400">
                    Gestionar asignaciones →
                </a>
            @endif
        </div>

        {{-- Leyenda --}}
        <div class="flex flex-wrap gap-4 mb-4 text-sm">
            <div class="flex items-center">
                <span class="w-3 h-3 bg-blue-500 rounded-full mr-2"></span>
                <span class="text-gray-600 dark:text-gray-400">Tiene asignaciones</span>
            </div>
            <div class="flex items-center">
                <span class="w-3 h-3 bg-green-500 rounded-full mr-2"></span>
                <span class="text-gray-600 dark:text-gray-400">Hoy</span>
            </div>
            <div class="flex items-center">
                <span class="w-3 h-3 bg-gray-300 dark:bg-gray-600 rounded-full mr-2"></span>
                <span class="text-gray-600 dark:text-gray-400">Fin de semana</span>
            </div>
        </div>

        {{-- Encabezados de días --}}
        <div class="grid grid-cols-7 gap-2 mb-2">
            @foreach(['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'] as $dayName)
                <div
                    class="text-center text-sm font-semibold text-gray-600 dark:text-gray-400 py-2 {{ in_array($dayName, ['Sáb', 'Dom']) ? 'text-gray-400' : '' }}">
                    {{ $dayName }}
                </div>
            @endforeach
        </div>

        {{-- Calendario --}}
        <div class="grid grid-cols-7 gap-2">
            {{-- Espacios vacíos --}}
            @for($i = 0; $i < $startPadding; $i++)
                <div class="h-20 bg-gray-50 dark:bg-gray-900 rounded-lg"></div>
            @endfor

            {{-- Días del mes --}}
            @for($day = 1; $day <= $lastDay->day; $day++)
                @php
                    $currentDate = Carbon\Carbon::create($currentYear, $currentMonth, $day);
                    $dateKey = $currentDate->format('Y-m-d');
                    $isWeekend = $currentDate->isWeekend();
                    $isToday = $currentDate->isToday();
                    $dayAssignments = $assignmentsByDate->get($dateKey, collect());
                    $hasAssignments = $dayAssignments->count() > 0;
                @endphp
                <div @if($hasAssignments && !$isWeekend)
                    onclick="openDayModal('{{ $dateKey }}', '{{ $currentDate->locale('es')->isoFormat('dddd D [de] MMMM') }}')"
                @endif class="h-20 p-2 rounded-lg border-2 transition-all overflow-hidden
                                {{ $isWeekend ? 'bg-gray-100 dark:bg-gray-900 border-gray-200 dark:border-gray-700 opacity-60' : 'bg-white dark:bg-gray-800 border-gray-200 dark:border-gray-700' }}
                                {{ $isToday ? 'ring-2 ring-green-500 ring-offset-2 dark:ring-offset-gray-800' : '' }}
                                {{ $hasAssignments && !$isWeekend ? 'cursor-pointer hover:bg-blue-50 dark:hover:bg-blue-900/50 hover:border-blue-400 dark:hover:border-blue-500' : '' }}
                            ">
                    <div class="flex justify-between items-start">
                        <span
                            class="text-sm font-bold {{ $isToday ? 'text-green-600 dark:text-green-400' : ($isWeekend ? 'text-gray-400 dark:text-gray-500' : 'text-gray-700 dark:text-gray-300') }}">
                            {{ $day }}
                        </span>
                        @if($hasAssignments && !$isWeekend)
                            <span
                                class="flex items-center justify-center w-5 h-5 text-xs font-bold text-white bg-blue-500 rounded-full shadow-sm">
                                {{ $dayAssignments->count() }}
                            </span>
                        @endif
                    </div>
                    @if($hasAssignments && !$isWeekend)
                        <div class="mt-1 space-y-0.5">
                            @foreach($dayAssignments->take(2) as $assignment)
                                <p class="text-xs text-blue-600 dark:text-blue-400 truncate leading-tight">
                                    {{ Str::before($assignment->user->name, ' ') }}
                                </p>
                            @endforeach
                            @if($dayAssignments->count() > 2)
                                <p class="text-xs text-gray-500 dark:text-gray-400">+{{ $dayAssignments->count() - 2 }} más</p>
                            @endif
                        </div>
                    @endif
                </div>
            @endfor
        </div>
    </div>
</div>