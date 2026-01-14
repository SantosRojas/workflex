<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('Reporte Horario Flexible') }} -
                {{ Carbon\Carbon::create($year, $month, 1)->locale('es')->monthName }} {{ $year }}
            </h2>
            <div class="flex gap-2">
                <a href="{{ route('flexible-schedule.export', ['month' => $month, 'year' => $year]) }}"
                    class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-500">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                        </path>
                    </svg>
                    Descargar Excel
                </a>
                <a href="{{ route('flexible-schedule.index', ['month' => $month, 'year' => $year]) }}"
                    class="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-500">
                    ← Volver al Listado
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            {{-- Navegación de meses --}}
            <div class="mb-6 flex justify-between items-center">
                @php
                    $prevMonth = Carbon\Carbon::create($year, $month, 1)->subMonth();
                    $nextMonth = Carbon\Carbon::create($year, $month, 1)->addMonth();

                    // Calcular estadísticas dinámicas
                    $uniqueSchedules = $byTime->count();
                    $earliestTime = $assignments->min('start_time');
                    $latestTime = $assignments->max('start_time');
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
                    <div class="text-gray-600 dark:text-gray-400">Total empleados con horario flexible</div>
                </div>
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <div class="text-3xl font-bold text-purple-600 dark:text-purple-400">
                        {{ $uniqueSchedules }}
                    </div>
                    <div class="text-gray-600 dark:text-gray-400">Horarios diferentes utilizados</div>
                </div>
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <div class="text-3xl font-bold text-green-600 dark:text-green-400">
                        {{ $earliestTime ? substr($earliestTime, 0, 5) : '--:--' }}
                    </div>
                    <div class="text-gray-600 dark:text-gray-400">Entrada más temprana</div>
                </div>
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <div class="text-3xl font-bold text-orange-600 dark:text-orange-400">
                        {{ $latestTime ? substr($latestTime, 0, 5) : '--:--' }}
                    </div>
                    <div class="text-gray-600 dark:text-gray-400">Entrada más tardía</div>
                </div>
            </div>


            {{-- Lista detallada de empleados --}}
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4">
                        Listado Completo de Empleados
                    </h3>

                    @if($assignments->isEmpty())
                        <p class="text-gray-500 dark:text-gray-400">No hay asignaciones para este mes.</p>
                    @else
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-700">
                                    <tr>
                                        <th
                                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                            Empleado
                                        </th>
                                        <th
                                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                            Área
                                        </th>
                                        <th
                                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                            Hora de Entrada
                                        </th>
                                        <th
                                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                            Hora de Almuerzo
                                        </th>
                                        <th
                                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                            Hora de Salida
                                        </th>
                                        <th
                                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                            Asignado por
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                    @foreach($assignments->sortBy('user.name') as $assignment)
                                        @php
                                            $dailyWorkMinutes = App\Models\SystemSetting::getInt('daily_work_minutes', 480);
                                            $lunchMinutes = 60; // 1 hora de almuerzo
                                            $totalMinutes = $dailyWorkMinutes + $lunchMinutes;

                                            $startTime = Carbon\Carbon::createFromTimeString($assignment->start_time);
                                            $endTime = $startTime->copy()->addMinutes($totalMinutes);

                                            // Calcular horas de trabajo para mostrar
                                            $workHours = floor($dailyWorkMinutes / 60);
                                            $workMins = $dailyWorkMinutes % 60;
                                        @endphp
                                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-750">
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                                    {{ $assignment->user->name }} {{ $assignment->user->last_name }}
                                                </div>
                                                <div class="text-xs text-gray-500 dark:text-gray-400">
                                                    {{ $assignment->user->email }}
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                                {{ $assignment->user->work_area }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span
                                                    class="px-3 py-1 bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200 rounded-full text-sm font-semibold">
                                                    {{ substr($assignment->start_time, 0, 5) }}
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span
                                                    class="px-3 py-1 bg-teal-100 dark:bg-teal-900 text-teal-800 dark:text-teal-200 rounded-full text-sm font-semibold">
                                                    🍽
                                                    {{ $assignment->lunch_start_time ? substr($assignment->lunch_start_time, 0, 5) : '12:00' }}
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span
                                                    class="px-3 py-1 bg-orange-100 dark:bg-orange-900 text-orange-800 dark:text-orange-200 rounded-full text-sm font-semibold">
                                                    {{ $endTime->format('H:i') }}
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                                {{ $assignment->assignedBy->name ?? 'Sistema' }}
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