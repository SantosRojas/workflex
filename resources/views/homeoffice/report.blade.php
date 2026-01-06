<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('Reporte Home Office') }} - {{ Carbon\Carbon::create($year, $month, 1)->locale('es')->monthName }}
                {{ $year }}
            </h2>
            <a href="{{ route('home-office.index', ['month' => $month, 'year' => $year]) }}"
                class="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-500">
                ← Volver al Calendario
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
                <a href="{{ route('home-office.report', ['month' => $prevMonth->month, 'year' => $prevMonth->year]) }}"
                    class="inline-flex items-center px-4 py-2 bg-gray-200 dark:bg-gray-700 rounded-md text-sm font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-300">
                    ← {{ $prevMonth->locale('es')->monthName }}
                </a>
                <span class="text-lg font-semibold text-gray-800 dark:text-gray-200">
                    {{ Carbon\Carbon::create($year, $month, 1)->locale('es')->monthName }} {{ $year }}
                </span>
                <a href="{{ route('home-office.report', ['month' => $nextMonth->month, 'year' => $nextMonth->year]) }}"
                    class="inline-flex items-center px-4 py-2 bg-gray-200 dark:bg-gray-700 rounded-md text-sm font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-300">
                    {{ $nextMonth->locale('es')->monthName }} →
                </a>
            </div>

            {{-- Estadísticas generales --}}
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <div class="text-3xl font-bold text-blue-600 dark:text-blue-400">
                        {{ $assignments->count() }}
                    </div>
                    <div class="text-gray-600 dark:text-gray-400">Total asignaciones</div>
                </div>
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <div class="text-3xl font-bold text-green-600 dark:text-green-400">
                        {{ $byUser->count() }}
                    </div>
                    <div class="text-gray-600 dark:text-gray-400">Empleados con home office</div>
                </div>
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <div class="text-3xl font-bold text-purple-600 dark:text-purple-400">
                        {{ $byDate->count() }}
                    </div>
                    <div class="text-gray-600 dark:text-gray-400">Días con home office</div>
                </div>
            </div>

            {{-- Resumen por usuario --}}
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4">Resumen por Empleado</h3>

                    @if($byUser->isEmpty())
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
                                            Días Asignados
                                        </th>
                                        <th
                                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                            Fechas
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                    @foreach($byUser as $data)
                                        <tr>
                                            <td
                                                class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-100">
                                                {{ $data['user']->name }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                                {{ $data['user']->work_area }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                                <span
                                                    class="px-2 py-1 rounded-full {{ $data['count'] >= 2 ? 'bg-yellow-100 dark:bg-yellow-800 text-yellow-800 dark:text-yellow-200' : 'bg-green-100 dark:bg-green-800 text-green-800 dark:text-green-200' }}">
                                                    {{ $data['count'] }} / 2
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                                                @foreach($data['dates'] as $date)
                                                    <span
                                                        class="inline-block bg-blue-100 dark:bg-blue-800 text-blue-800 dark:text-blue-200 px-2 py-1 rounded text-xs mr-1 mb-1">
                                                        {{ $date->format('d/m') }}
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

            {{-- Resumen por fecha --}}
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4">Distribución por Fecha</h3>

                    @if($byDate->isEmpty())
                        <p class="text-gray-500 dark:text-gray-400">No hay asignaciones para este mes.</p>
                    @else
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            @foreach($byDate->sortKeys() as $dateStr => $dateAssignments)
                                @php
                                    $date = Carbon\Carbon::parse($dateStr);
                                @endphp
                                <div
                                    class="border border-gray-200 dark:border-gray-700 rounded-lg p-4 {{ $dateAssignments->count() >= 7 ? 'bg-red-50 dark:bg-red-900' : '' }}">
                                    <div class="flex justify-between items-center mb-2">
                                        <span class="font-semibold text-gray-800 dark:text-gray-200">
                                            {{ $date->locale('es')->isoFormat('dddd D') }}
                                        </span>
                                        <span
                                            class="text-sm px-2 py-1 rounded {{ $dateAssignments->count() >= 7 ? 'bg-red-200 dark:bg-red-800 text-red-800 dark:text-red-200' : 'bg-green-200 dark:bg-green-800 text-green-800 dark:text-green-200' }}">
                                            {{ $dateAssignments->count() }}/7
                                        </span>
                                    </div>
                                    <div class="space-y-1">
                                        @foreach($dateAssignments as $assignment)
                                            <div class="text-sm text-gray-600 dark:text-gray-400">
                                                • {{ $assignment->user->name }}
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>