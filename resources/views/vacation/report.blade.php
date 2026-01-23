<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('Reporte de Vacaciones') }} - {{ $year }}
            </h2>
            <div class="flex gap-2">
                <a href="{{ route('vacation.export', ['year' => $year]) }}"
                    class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-500">
                    <x-icons.download class="w-4 h-4 mr-2" />
                    Exportar CSV
                </a>
                <a href="{{ route('vacation.index', ['year' => $year]) }}"
                    class="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-500">
                    ← Volver
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            {{-- Selector de año --}}
            <div class="mb-6 flex gap-2 items-center">
                <span class="text-gray-600 dark:text-gray-400 text-sm">Año:</span>
                @foreach($availableYears as $y)
                    <a href="{{ route('vacation.report', ['year' => $y]) }}"
                       class="px-3 py-1 rounded {{ $y == $year ? 'bg-indigo-600 text-white' : 'bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-300 dark:hover:bg-gray-600' }}">
                        {{ $y }}
                    </a>
                @endforeach
            </div>

            {{-- Estadísticas generales --}}
            @php
                $totalEmployees = count($summaries);
                $totalDaysUsed = collect($summaries)->sum(fn($s) => $s['summary']['used_days']);
                $totalDaysAvailable = collect($summaries)->sum(fn($s) => $s['summary']['available_days']);
                $averageUsage = $totalEmployees > 0 ? round(collect($summaries)->avg(fn($s) => $s['summary']['percentage_used']), 1) : 0;
            @endphp
            
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-4">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-blue-100 dark:bg-blue-800 mr-3">
                            <x-icons.users class="w-5 h-5 text-blue-600 dark:text-blue-300" />
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Empleados</p>
                            <p class="text-xl font-bold text-gray-900 dark:text-gray-100">{{ $totalEmployees }}</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-4">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-green-100 dark:bg-green-800 mr-3">
                            <x-icons.check class="w-5 h-5 text-green-600 dark:text-green-300" />
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Días Usados</p>
                            <p class="text-xl font-bold text-gray-900 dark:text-gray-100">{{ $totalDaysUsed }}</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-4">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-yellow-100 dark:bg-yellow-800 mr-3">
                            <x-icons.time class="w-5 h-5 text-yellow-600 dark:text-yellow-300" />
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Días Pendientes</p>
                            <p class="text-xl font-bold text-gray-900 dark:text-gray-100">{{ $totalDaysAvailable }}</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-4">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-purple-100 dark:bg-purple-800 mr-3">
                            <x-icons.chart class="w-5 h-5 text-purple-600 dark:text-purple-300" />
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Uso Promedio</p>
                            <p class="text-xl font-bold text-gray-900 dark:text-gray-100">{{ $averageUsage }}%</p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Tabla de reporte --}}
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4 flex items-center">
                        <x-icons.list class="w-5 h-5 mr-2" /> Detalle por Empleado
                    </h3>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        Empleado
                                    </th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        Área
                                    </th>
                                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        Días Usados
                                    </th>
                                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        Fines de Semana
                                    </th>
                                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        Disponibles
                                    </th>
                                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        Progreso
                                    </th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        Períodos
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                @foreach($summaries as $data)
                                    @php 
                                        $member = $data['user'];
                                        $summary = $data['summary'];
                                    @endphp
                                    <tr>
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            <span class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                                {{ $member->name }} {{ $member->last_name }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            <span class="text-sm text-gray-500 dark:text-gray-400">
                                                {{ $member->work_area }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap text-center">
                                            <span class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                                {{ $summary['used_days'] }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap text-center">
                                            <span class="text-xs px-2 py-1 rounded-full {{ $summary['weekend_days_used'] >= $summary['max_weekend_days'] ? 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200' : 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200' }}">
                                                {{ $summary['weekend_days_used'] }}/{{ $summary['max_weekend_days'] }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap text-center">
                                            <span class="text-sm {{ $summary['available_days'] > 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                                {{ $summary['available_days'] }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            <div class="flex items-center">
                                                <div class="w-24 bg-gray-200 dark:bg-gray-600 rounded-full h-2 mr-2">
                                                    <div class="bg-indigo-600 h-2 rounded-full" style="width: {{ $summary['percentage_used'] }}%"></div>
                                                </div>
                                                <span class="text-xs text-gray-500 dark:text-gray-400">
                                                    {{ $summary['percentage_used'] }}%
                                                </span>
                                            </div>
                                        </td>
                                        <td class="px-4 py-3">
                                            @if($summary['assignments']->count() > 0)
                                                <div class="space-y-1">
                                                    @foreach($summary['assignments'] as $assignment)
                                                        <div class="text-xs">
                                                            <span class="px-1.5 py-0.5 rounded {{ $assignment->status_class }}">
                                                                {{ $assignment->start_date->format('d/m') }} - {{ $assignment->end_date->format('d/m') }}
                                                            </span>
                                                            <span class="text-gray-500 dark:text-gray-400 ml-1">
                                                                ({{ $assignment->calendar_days }}d)
                                                            </span>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            @else
                                                <span class="text-xs text-gray-400 dark:text-gray-500">Sin asignar</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- Calendario visual de vacaciones --}}
            <div class="mt-6 bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4 flex items-center">
                        <x-icons.calendar class="w-5 h-5 mr-2" /> Línea de Tiempo - {{ $year }}
                    </h3>

                    <div class="space-y-3">
                        @foreach($summaries as $data)
                            @php 
                                $member = $data['user'];
                                $summary = $data['summary'];
                            @endphp
                            <div class="flex items-center gap-3">
                                <div class="w-40 truncate text-sm text-gray-700 dark:text-gray-300">
                                    {{ $member->name }}
                                </div>
                                <div class="flex-1 h-6 bg-gray-100 dark:bg-gray-700 rounded relative">
                                    @foreach($summary['assignments'] as $assignment)
                                        @php
                                            $startDay = $assignment->start_date->dayOfYear;
                                            $endDay = $assignment->end_date->dayOfYear;
                                            $daysInYear = $assignment->start_date->isLeapYear() ? 366 : 365;
                                            $left = ($startDay / $daysInYear) * 100;
                                            $width = (($endDay - $startDay + 1) / $daysInYear) * 100;
                                            
                                            $bgColor = match($assignment->status) {
                                                'completed' => 'bg-gray-400',
                                                'active' => 'bg-green-500',
                                                'upcoming' => 'bg-blue-500',
                                                default => 'bg-indigo-500',
                                            };
                                        @endphp
                                        <div class="absolute h-full {{ $bgColor }} rounded opacity-80 hover:opacity-100 cursor-pointer"
                                             style="left: {{ $left }}%; width: {{ max($width, 0.5) }}%;"
                                             title="{{ $assignment->formatted_period }} ({{ $assignment->calendar_days }} días)">
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>

                    {{-- Leyenda de meses --}}
                    <div class="mt-4 flex justify-between text-xs text-gray-500 dark:text-gray-400 ml-43">
                        @foreach(['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'] as $mes)
                            <span>{{ $mes }}</span>
                        @endforeach
                    </div>

                    {{-- Leyenda de colores --}}
                    <div class="mt-4 flex gap-4 text-xs">
                        <div class="flex items-center gap-1">
                            <div class="w-3 h-3 bg-gray-400 rounded"></div>
                            <span class="text-gray-600 dark:text-gray-400">Completado</span>
                        </div>
                        <div class="flex items-center gap-1">
                            <div class="w-3 h-3 bg-green-500 rounded"></div>
                            <span class="text-gray-600 dark:text-gray-400">En curso</span>
                        </div>
                        <div class="flex items-center gap-1">
                            <div class="w-3 h-3 bg-blue-500 rounded"></div>
                            <span class="text-gray-600 dark:text-gray-400">Próximo</span>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
