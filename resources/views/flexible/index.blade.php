<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('Asignación de Horario Flexible') }} -
                {{ Carbon\Carbon::create($year, $month, 1)->locale('es')->monthName }} {{ $year }}
            </h2>
            @if(Auth::user()->canManageAssignments())
                <a href="{{ route('flexible-schedule.report', ['month' => $month, 'year' => $year]) }}"
                    class="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-500">
                    Ver Reporte
                </a>
            @endif
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">

            {{-- Mensajes de éxito/error --}}
            @if(session('success'))
                <div
                    class="mb-4 bg-green-100 dark:bg-green-900 border border-green-400 dark:border-green-600 text-green-700 dark:text-green-300 px-4 py-3 rounded">
                    {{ session('success') }}
                </div>
            @endif

            @if($errors->any())
                <div
                    class="mb-4 bg-red-100 dark:bg-red-900 border border-red-400 dark:border-red-600 text-red-700 dark:text-red-300 px-4 py-3 rounded">
                    @foreach($errors->all() as $error)
                        <p>{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            {{-- Alerta si el área no puede tener horario flexible --}}
            @if(!$areaCanHaveFlexible && !Auth::user()->isAdmin())
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-8 text-center">
                        <div
                            class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-red-100 dark:bg-red-900 mb-4">
                            <x-icons.warning class="h-8 w-8 text-red-600 dark:text-red-400" />
                        </div>
                        <h3 class="text-xl font-semibold text-gray-800 dark:text-gray-200 mb-2">
                            Área no elegible
                        </h3>
                        <p class="text-gray-600 dark:text-gray-400">
                            Tu área (<strong>{{ Auth::user()->work_area }}</strong>) no puede acceder al horario flexible.
                        </p>
                        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                            Las áreas de Servicio al Cliente, Ventas, Facturación y Almacén no tienen acceso a esta
                            funcionalidad.
                        </p>
                    </div>
                </div>
            @elseif($planningPeriod['isActive'] || Auth::user()->isAdmin())
                {{-- Período activo - Mostrar formulario de asignación --}}
                @if(Auth::user()->canManageAssignments())
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            {{-- Información del período activo --}}
                            <div class="mb-6 p-4 rounded-lg bg-green-100 dark:bg-green-900">
                                <p class="text-sm text-green-800 dark:text-green-200">
                                    <span class="font-semibold"> Período de planificación activo</span>
                                    <br>
                                    <span class="text-xs">Del {{ $planningPeriod['start']->format('d/m/Y') }} al
                                        {{ $planningPeriod['end']->format('d/m/Y') }}</span>
                                </p>
                            </div>

                            <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4">
                                Asignar Horario Flexible para
                                {{ Carbon\Carbon::create($year, $month, 1)->locale('es')->monthName }}
                                @if(Auth::user()->isAdmin() && !$planningPeriod['isActive'])
                                    <span class="text-sm font-normal text-yellow-600">(Modo Admin - fuera de período)</span>
                                @endif
                            </h3>

                            {{-- Información de horarios disponibles --}}
                            <div class="mb-6 bg-blue-50 dark:bg-blue-900 p-4 rounded-lg">
                                <p class="text-sm text-blue-800 dark:text-blue-200 flex items-center">
                                    <x-icons.info class="w-4 h-4 mr-2" />
                                    <span class="font-semibold mr-1">Horarios permitidos:</span> Entre 07:00 y 11:59 AM
                                </p>
                            </div>

                            <form action="{{ route('flexible-schedule.store') }}" method="POST" class="space-y-4">
                                @csrf

                                {{-- Campos ocultos para mes y año --}}
                                <input type="hidden" name="month" value="{{ $month }}">
                                <input type="hidden" name="year" value="{{ $year }}">

                                <div>
                                    <x-input-label for="user_id" value="Empleado" />
                                    <select name="user_id" id="user_id" required
                                        class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                        <option value="">Seleccionar empleado...</option>
                                        @foreach($teamMembers as $member)
                                            @php
                                                $hasAssignment = $assignments->where('user_id', $member->id)->first();
                                            @endphp
                                            <option value="{{ $member->id }}" {{ $hasAssignment ? 'disabled' : '' }}>
                                                {{ strtok($member->name, ' ') . ' ' . strtok($member->last_name, ' ') }}
                                                @if(Auth::user()->isAdmin())
                                                    [{{ $member->work_area }}]
                                                @endif
                                                @if($hasAssignment)
                                                    (Ya asignado: {{ substr($hasAssignment->start_time, 0, 5) }})
                                                @endif
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div>
                                    <x-input-label for="start_time" value="Horario de Entrada" />

                                    {{-- Botones de horarios rápidos --}}
                                    <div class="mt-2 flex flex-wrap gap-2 mb-3">
                                        @foreach($allowedTimes as $time)
                                            <button type="button"
                                                onclick="document.getElementById('start_time').value = '{{ $time }}'"
                                                class="px-4 py-2 rounded-lg text-sm font-medium border-2 transition-all
                                                                                                                                {{ $time == '08:00' ? 'border-green-300 bg-green-50 text-green-700 hover:bg-green-100 dark:border-green-600 dark:bg-green-900 dark:text-green-300' : '' }}
                                                                                                                                {{ $time == '08:30' ? 'border-yellow-300 bg-yellow-50 text-yellow-700 hover:bg-yellow-100 dark:border-yellow-600 dark:bg-yellow-900 dark:text-yellow-300' : '' }}
                                                                                                                                {{ $time == '09:00' ? 'border-blue-300 bg-blue-50 text-blue-700 hover:bg-blue-100 dark:border-blue-600 dark:bg-blue-900 dark:text-blue-300' : '' }}">
                                                {{ $time }}
                                            </button>
                                        @endforeach
                                    </div>

                                    {{-- Campo de texto para horario personalizado --}}
                                    <input type="time" name="start_time" id="start_time" required min="07:00" max="11:59"
                                        value="08:00"
                                        class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                        Selecciona un horario predefinido o ingresa uno personalizado (07:00 - 11:59 AM)
                                    </p>
                                </div>

                                <div>
                                    <x-input-label for="lunch_start_time" value="Hora de Almuerzo" />

                                    {{-- Botones de horarios de almuerzo rápidos --}}
                                    <div class="mt-2 flex flex-wrap gap-2 mb-3">
                                        @foreach($allowedLunchTimes as $time)
                                            <button type="button"
                                                onclick="document.getElementById('lunch_start_time').value = '{{ $time }}'"
                                                class="px-4 py-2 rounded-lg text-sm font-medium border-2 transition-all
                                                                {{ $time == '11:00' ? 'border-lime-300 bg-lime-50 text-lime-700 hover:bg-lime-100 dark:border-lime-600 dark:bg-lime-900 dark:text-lime-300' : '' }}
                                                                {{ $time == '12:00' ? 'border-orange-300 bg-orange-50 text-orange-700 hover:bg-orange-100 dark:border-orange-600 dark:bg-orange-900 dark:text-orange-300' : '' }}
                                                                {{ $time == '13:00' ? 'border-red-300 bg-red-50 text-red-700 hover:bg-red-100 dark:border-red-600 dark:bg-red-900 dark:text-red-300' : '' }}
                                                                {{ $time == '14:00' ? 'border-purple-300 bg-purple-50 text-purple-700 hover:bg-purple-100 dark:border-purple-600 dark:bg-purple-900 dark:text-purple-300' : '' }}
                                                                {{ $time == '15:00' ? 'border-pink-300 bg-pink-50 text-pink-700 hover:bg-pink-100 dark:border-pink-600 dark:bg-pink-900 dark:text-pink-300' : '' }}">
                                                {{ $time }}
                                            </button>
                                        @endforeach
                                    </div>

                                    {{-- Campo de texto para horario de almuerzo personalizado --}}
                                    <input type="time" name="lunch_start_time" id="lunch_start_time" min="11:00" max="15:59"
                                        value="12:00"
                                        class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                        Selecciona la hora de inicio del almuerzo (11:00 - 15:59)
                                    </p>
                                </div>

                                <div class="pt-4">
                                    <x-primary-button class="w-full justify-center">
                                        <x-icons.check class="w-5 h-5 mr-2" />
                                        Asignar Horario Flexible
                                    </x-primary-button>
                                </div>
                            </form>
                        </div>
                    </div>

                    {{-- Lista de asignaciones actuales del mes --}}
                    @if($assignments->count() > 0)
                        <div class="mt-6 bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                            <div class="p-6">
                                <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4">
                                    Asignaciones de {{ Carbon\Carbon::create($year, $month, 1)->locale('es')->monthName }}
                                </h3>
                                <div class="space-y-2">
                                    @foreach($assignments->sortBy('start_time') as $assignment)
                                        @php
                                            $assignmentTime = substr($assignment->start_time, 0, 5);
                                            $lunchTime = $assignment->lunch_start_time ? substr($assignment->lunch_start_time, 0, 5) : '12:00';
                                        @endphp
                                        <div class="flex justify-between items-center p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                            <div class="flex items-center space-x-3">
                                                <span
                                                    class="px-3 py-1 rounded-full text-sm font-semibold
                                                                                                                                                    {{ $assignmentTime == '08:00' ? 'bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100' : '' }}
                                                                                                                                                    {{ $assignmentTime == '08:30' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-800 dark:text-yellow-100' : '' }}
                                                                                                                                                    {{ $assignmentTime == '09:00' ? 'bg-blue-100 text-blue-800 dark:bg-blue-800 dark:text-blue-100' : '' }}
                                                                                                                                                    {{ !in_array($assignmentTime, ['08:00', '08:30', '09:00']) ? 'bg-purple-100 text-purple-800 dark:bg-purple-800 dark:text-purple-100' : '' }}">
                                                    {{ $assignmentTime }}
                                                </span>
                                                <span
                                                    class="px-2 py-1 rounded-full text-xs font-medium bg-teal-100 text-teal-800 dark:bg-teal-800 dark:text-teal-100 flex items-center"
                                                    title="Hora de almuerzo">
                                                    <x-icons.lunch class="w-3 h-3 mr-1" /> {{ $lunchTime }}
                                                </span>
                                                <div>
                                                    <span
                                                        class="font-medium text-gray-800 dark:text-gray-200">{{ $assignment->user->name }}</span>
                                                    @if(Auth::user()->isAdmin())
                                                        <span
                                                            class="text-xs text-gray-500 dark:text-gray-400 ml-1">[{{ $assignment->user->work_area }}]</span>
                                                    @endif
                                                </div>
                                            </div>
                                            @if(Auth::user()->canManageAssignments() && (Auth::user()->isAdmin() || $assignment->user->work_area === Auth::user()->work_area))
                                                <div class="flex items-center gap-2">
                                                    {{-- Botón editar --}}
                                                    <button type="button"
                                                        onclick="openEditModal({{ $assignment->id }}, '{{ $assignmentTime }}', '{{ $lunchTime }}')"
                                                        class="text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300"
                                                        title="Editar asignación">
                                                        <x-icons.edit class="w-5 h-5" />
                                                    </button>
                                                    {{-- Formulario eliminar --}}
                                                    <form action="{{ route('flexible-schedule.destroy', $assignment) }}" method="POST" class="inline-flex items-center">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit"
                                                            class="text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300"
                                                            title="Eliminar asignación"
                                                            onclick="return confirm('¿Eliminar esta asignación?')">
                                                            <x-icons.delete class="w-5 h-5" />
                                                        </button>
                                                    </form>
                                                </div>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>

                                {{-- Resumen por horario --}}
                                <div class="mt-6 pt-4 border-t border-gray-200 dark:border-gray-600">
                                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-2">Resumen:</p>
                                    @php
                                        // Agrupar por horario y contar
                                        $groupedByTime = $assignments->groupBy(fn($a) => substr($a->start_time, 0, 5))->sortKeys();
                                    @endphp
                                    <div class="flex flex-wrap gap-4">
                                        @foreach($groupedByTime as $time => $assignmentsForTime)
                                            <div class="flex items-center space-x-2">
                                                <span
                                                    class="px-2 py-1 rounded text-xs font-semibold
                                                                                                                                                    {{ $time == '08:00' ? 'bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100' : '' }}
                                                                                                                                                    {{ $time == '08:30' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-800 dark:text-yellow-100' : '' }}
                                                                                                                                                    {{ $time == '09:00' ? 'bg-blue-100 text-blue-800 dark:bg-blue-800 dark:text-blue-100' : '' }}
                                                                                                                                                    {{ !in_array($time, ['08:00', '08:30', '09:00']) ? 'bg-purple-100 text-purple-800 dark:bg-purple-800 dark:text-purple-100' : '' }}">
                                                    {{ $time }}
                                                </span>
                                                <span
                                                    class="text-sm text-gray-600 dark:text-gray-400">{{ $assignmentsForTime->count() }}
                                                    persona(s)</span>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                @else
                    {{-- Usuario sin permisos --}}
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6 text-center">
                            <x-icons.warning class="mx-auto h-12 w-12 text-gray-400" />
                            <h3 class="mt-2 text-lg font-medium text-gray-900 dark:text-gray-100">Acceso restringido</h3>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                No tienes permisos para asignar horarios flexibles.
                            </p>
                        </div>
                    </div>
                @endif
            @else
                {{-- Período NO activo - Mostrar mensaje de espera --}}
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-8 text-center">
                        <div
                            class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-yellow-100 dark:bg-yellow-900 mb-4">
                            <x-icons.time class="h-8 w-8 text-yellow-600 dark:text-yellow-400" />
                        </div>

                        @php
                            $status = $planningPeriod['status'] ?? 'before';
                            // Calcular el siguiente período para el estado 'ended'
                            if ($status === 'ended') {
                                $nextMonth = $month == 12 ? 1 : $month + 1;
                                $nextYear = $month == 12 ? $year + 1 : $year;
                                $nextPeriod = App\Services\PlanningPeriodService::getPlanningPeriod($nextMonth, $nextYear);
                            }
                        @endphp

                        @if($status === 'just_ended')
                            {{-- Período recién terminado (menos de 3 días) --}}
                            <h3 class="text-xl font-semibold text-gray-800 dark:text-gray-200 mb-2 flex items-center justify-center gap-2">
                                <x-icons.warning class="w-6 h-6 text-red-500" /> El período de asignación finalizó
                            </h3>

                            <p class="text-gray-600 dark:text-gray-400 mb-6">
                                El período de planificación para
                                <strong>{{ Carbon\Carbon::create($year, $month, 1)->locale('es')->monthName }}
                                    {{ $year }}</strong>
                                terminó el {{ $planningPeriod['end']->format('d/m/Y') }}.
                            </p>

                            <div class="bg-red-50 dark:bg-red-900 p-4 rounded-lg inline-block">
                                <p class="text-red-800 dark:text-red-200">
                                    <span class="font-semibold flex items-center justify-center gap-2"><x-icons.calendar class="w-5 h-5" /> El período fue:</span>
                                    <span class="text-lg">{{ $planningPeriod['start']->format('d/m/Y') }} -
                                        {{ $planningPeriod['end']->format('d/m/Y') }}</span>
                                </p>
                            </div>

                            <p class="mt-6 text-sm text-gray-500 dark:text-gray-400">
                                Las asignaciones para este mes ya no pueden ser modificadas.
                            </p>
                        @elseif($status === 'ended')
                            {{-- Período terminado hace más de 3 días - Mostrar siguiente período --}}
                            <h3 class="text-xl font-semibold text-gray-800 dark:text-gray-200 mb-2">
                                Aún no es momento de asignar
                            </h3>

                            <p class="text-gray-600 dark:text-gray-400 mb-6">
                                El período de planificación para
                                <strong>{{ Carbon\Carbon::create($nextYear, $nextMonth, 1)->locale('es')->monthName }}
                                    {{ $nextYear }}</strong>
                                aún no está activo.
                            </p>

                            <div class="bg-blue-50 dark:bg-blue-900 p-4 rounded-lg inline-block">
                                <p class="text-blue-800 dark:text-blue-200">
                                    <span class="font-semibold flex items-center justify-center gap-2"><x-icons.calendar class="w-5 h-5" /> Período de planificación:</span>
                                    <span class="text-lg">{{ $nextPeriod['start']->format('d/m/Y') }} -
                                        {{ $nextPeriod['end']->format('d/m/Y') }}</span>
                                </p>
                            </div>

                            <p class="mt-6 text-sm text-gray-500 dark:text-gray-400">
                                Regresa durante el período indicado para realizar las asignaciones de horario flexible.
                            </p>
                        @else
                            {{-- Período aún no comienza --}}
                            <h3 class="text-xl font-semibold text-gray-800 dark:text-gray-200 mb-2">
                                Aún no es momento de asignar
                            </h3>

                            <p class="text-gray-600 dark:text-gray-400 mb-6">
                                El período de planificación para
                                <strong>{{ Carbon\Carbon::create($year, $month, 1)->locale('es')->monthName }}
                                    {{ $year }}</strong>
                                aún no está activo.
                            </p>

                            <div class="bg-blue-50 dark:bg-blue-900 p-4 rounded-lg inline-block">
                                <p class="text-blue-800 dark:text-blue-200">
                                    <span class="font-semibold flex items-center justify-center gap-2"><x-icons.calendar class="w-5 h-5" /> Período de planificación:</span>
                                    <span class="text-lg">{{ $planningPeriod['start']->format('d/m/Y') }} -
                                        {{ $planningPeriod['end']->format('d/m/Y') }}</span>
                                </p>
                            </div>

                            <p class="mt-6 text-sm text-gray-500 dark:text-gray-400">
                                Regresa durante el período indicado para realizar las asignaciones de horario flexible.
                            </p>
                        @endif
                    </div>
                </div>
            @endif
        </div>
    </div>

    {{-- Modal de edición --}}
    <div id="editModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white dark:bg-gray-800">
            <div class="mt-3">
                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Editar Horario</h3>
                <form id="editForm" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="mb-4">
                        <x-input-label for="edit_start_time" value="Nuevo Horario de Entrada" />

                        {{-- Botones de horarios rápidos --}}
                        <div class="mt-2 flex flex-wrap gap-2 mb-3">
                            @foreach($allowedTimes as $time)
                                <button type="button"
                                    onclick="document.getElementById('edit_start_time').value = '{{ $time }}'"
                                    class="px-3 py-1 rounded-lg text-sm font-medium border-2 transition-all
                                                                    {{ $time == '08:00' ? 'border-green-300 bg-green-50 text-green-700 hover:bg-green-100 dark:border-green-600 dark:bg-green-900 dark:text-green-300' : '' }}
                                                                    {{ $time == '08:30' ? 'border-yellow-300 bg-yellow-50 text-yellow-700 hover:bg-yellow-100 dark:border-yellow-600 dark:bg-yellow-900 dark:text-yellow-300' : '' }}
                                                                    {{ $time == '09:00' ? 'border-blue-300 bg-blue-50 text-blue-700 hover:bg-blue-100 dark:border-blue-600 dark:bg-blue-900 dark:text-blue-300' : '' }}">
                                    {{ $time }}
                                </button>
                            @endforeach
                        </div>

                        <input type="time" name="start_time" id="edit_start_time" required min="07:00" max="10:59"
                            class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                    </div>

                    <div class="mb-4">
                        <x-input-label for="edit_lunch_start_time" value="Hora de Almuerzo" />

                        {{-- Botones de horarios de almuerzo rápidos --}}
                        <div class="mt-2 flex flex-wrap gap-2 mb-3">
                            @foreach($allowedLunchTimes as $time)
                                <button type="button"
                                    onclick="document.getElementById('edit_lunch_start_time').value = '{{ $time }}'"
                                    class="px-3 py-1 rounded-lg text-sm font-medium border-2 transition-all
                                            {{ $time == '11:00' ? 'border-lime-300 bg-lime-50 text-lime-700 hover:bg-lime-100 dark:border-lime-600 dark:bg-lime-900 dark:text-lime-300' : '' }}
                                            {{ $time == '12:00' ? 'border-orange-300 bg-orange-50 text-orange-700 hover:bg-orange-100 dark:border-orange-600 dark:bg-orange-900 dark:text-orange-300' : '' }}
                                            {{ $time == '13:00' ? 'border-red-300 bg-red-50 text-red-700 hover:bg-red-100 dark:border-red-600 dark:bg-red-900 dark:text-red-300' : '' }}
                                            {{ $time == '14:00' ? 'border-purple-300 bg-purple-50 text-purple-700 hover:bg-purple-100 dark:border-purple-600 dark:bg-purple-900 dark:text-purple-300' : '' }}
                                            {{ $time == '15:00' ? 'border-pink-300 bg-pink-50 text-pink-700 hover:bg-pink-100 dark:border-pink-600 dark:bg-pink-900 dark:text-pink-300' : '' }}">
                                    {{ $time }}
                                </button>
                            @endforeach
                        </div>

                        <input type="time" name="lunch_start_time" id="edit_lunch_start_time" min="11:00" max="15:59"
                            class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                    </div>

                    <div class="flex justify-end space-x-2">
                        <button type="button" onclick="closeEditModal()"
                            class="px-4 py-2 bg-gray-300 dark:bg-gray-600 text-gray-700 dark:text-gray-200 rounded-md hover:bg-gray-400 dark:hover:bg-gray-500">
                            Cancelar
                        </button>
                        <x-primary-button>
                            Guardar
                        </x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function openEditModal(id, currentTime, currentLunchTime) {
            document.getElementById('editForm').action = '/flexible-schedule/' + id;
            document.getElementById('edit_start_time').value = currentTime;
            document.getElementById('edit_lunch_start_time').value = currentLunchTime || '12:00';
            document.getElementById('editModal').classList.remove('hidden');
        }

        function closeEditModal() {
            document.getElementById('editModal').classList.add('hidden');
        }
    </script>
</x-app-layout>