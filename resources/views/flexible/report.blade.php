<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('Reporte Horario Flexible') }} -
                {{ Carbon\Carbon::create($year, $month, 1)->locale('es')->monthName }} {{ $year }}
            </h2>
            <a href="{{ route('flexible-schedule.index', ['month' => $month, 'year' => $year]) }}"
                class="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-500">
                ← Volver al Listado
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            {{-- Navegación de meses --}}
            <div class="mb-6 flex justify-between items-center">
                @php
                    $prevMonth = Carbon\Carbon::create($year, $month, 1)->subMonth();
                    $nextMonth = Carbon\Carbon::create($year, $month, 1)->addMonth();
                @endphp
                <a href="{{ route('flexible-schedule.report', ['month' => $prevMonth->month, 'year' => $prevMonth->year]) }}"
                    class="inline-flex items-center px-4 py-2 bg-gray-200 dark:bg-gray-700 rounded-md text-sm font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-300">
                    ← {{ $prevMonth->locale('es')->monthName }}
                </a>
                <span class="text-lg font-semibold text-gray-800 dark:text-gray-200">
                    {{ Carbon\Carbon::create($year, $month, 1)->locale('es')->monthName }} {{ $year }}
                </span>
                <a href="{{ route('flexible-schedule.report', ['month' => $nextMonth->month, 'year' => $nextMonth->year]) }}"
                    class="inline-flex items-center px-4 py-2 bg-gray-200 dark:bg-gray-700 rounded-md text-sm font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-300">
                    {{ $nextMonth->locale('es')->monthName }} →
                </a>
            </div>

            {{-- Estadísticas generales --}}
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <div class="text-3xl font-bold text-blue-600 dark:text-blue-400">
                        {{ $assignments->count() }}
                    </div>
                    <div class="text-gray-600 dark:text-gray-400">Total asignaciones</div>
                </div>
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <div class="text-3xl font-bold text-green-600 dark:text-green-400">
                        {{ $byTime->has('08:00:00') ? $byTime['08:00:00']->count() : 0 }}
                    </div>
                    <div class="text-gray-600 dark:text-gray-400">Entrada 08:00</div>
                </div>
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <div class="text-3xl font-bold text-yellow-600 dark:text-yellow-400">
                        {{ $byTime->has('08:30:00') ? $byTime['08:30:00']->count() : 0 }}
                    </div>
                    <div class="text-gray-600 dark:text-gray-400">Entrada 08:30</div>
                </div>
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <div class="text-3xl font-bold text-purple-600 dark:text-purple-400">
                        {{ $byTime->has('09:00:00') ? $byTime['09:00:00']->count() : 0 }}
                    </div>
                    <div class="text-gray-600 dark:text-gray-400">Entrada 09:00</div>
                </div>
            </div>

            {{-- Distribución por horario --}}
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4">Distribución por Horario
                    </h3>

                    @if($assignments->isEmpty())
                        <p class="text-gray-500 dark:text-gray-400">No hay asignaciones para este mes.</p>
                    @else
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            @foreach(['08:00:00' => '08:00', '08:30:00' => '08:30', '09:00:00' => '09:00'] as $timeKey => $timeLabel)
                                <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                                    <h4 class="text-lg font-semibold mb-3 
                                                {{ $timeLabel == '08:00' ? 'text-green-600 dark:text-green-400' : '' }}
                                                {{ $timeLabel == '08:30' ? 'text-yellow-600 dark:text-yellow-400' : '' }}
                                                {{ $timeLabel == '09:00' ? 'text-blue-600 dark:text-blue-400' : '' }}">
                                        🕐 Entrada {{ $timeLabel }}
                                    </h4>

                                    @if($byTime->has($timeKey))
                                        <div class="space-y-2">
                                            @foreach($byTime[$timeKey] as $assignment)
                                                <div class="flex justify-between items-center text-sm">
                                                    <span class="text-gray-700 dark:text-gray-300">{{ $assignment->user->name }}</span>
                                                    <span
                                                        class="text-gray-500 dark:text-gray-400 text-xs">{{ $assignment->user->work_area }}</span>
                                                </div>
                                            @endforeach
                                        </div>
                                    @else
                                        <p class="text-gray-400 text-sm">Sin asignaciones</p>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            {{-- Distribución por área --}}
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4">Distribución por Área</h3>

                    @if($byArea->isEmpty())
                        <p class="text-gray-500 dark:text-gray-400">No hay asignaciones para este mes.</p>
                    @else
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-700">
                                    <tr>
                                        <th
                                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                            Área
                                        </th>
                                        <th
                                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                            Total
                                        </th>
                                        <th
                                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                            08:00
                                        </th>
                                        <th
                                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                            08:30
                                        </th>
                                        <th
                                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                            09:00
                                        </th>
                                        <th
                                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                            Empleados
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                    @foreach($byArea as $area => $areaAssignments)
                                        <tr>
                                            <td
                                                class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-100">
                                                {{ $area }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                                <span class="px-2 py-1 bg-gray-100 dark:bg-gray-700 rounded-full">
                                                    {{ $areaAssignments->count() }}
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                                <span
                                                    class="px-2 py-1 bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100 rounded-full">
                                                    {{ $areaAssignments->filter(fn($a) => $a->start_time == '08:00:00')->count() }}
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                                <span
                                                    class="px-2 py-1 bg-yellow-100 text-yellow-800 dark:bg-yellow-800 dark:text-yellow-100 rounded-full">
                                                    {{ $areaAssignments->filter(fn($a) => $a->start_time == '08:30:00')->count() }}
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                                <span
                                                    class="px-2 py-1 bg-blue-100 text-blue-800 dark:bg-blue-800 dark:text-blue-100 rounded-full">
                                                    {{ $areaAssignments->filter(fn($a) => $a->start_time == '09:00:00')->count() }}
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                                                @foreach($areaAssignments as $assignment)
                                                    <span
                                                        class="inline-block bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded text-xs mr-1 mb-1">
                                                        {{ $assignment->user->name }}
                                                    </span>
                                                @endforeach
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>