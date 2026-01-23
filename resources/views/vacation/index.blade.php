<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('Programación de Vacaciones') }} - {{ $year }}
            </h2>
            @if(Auth::user()->canManageAssignments())
                <a href="{{ route('vacation.report', ['year' => $year]) }}"
                    class="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-500">
                    Ver Reporte
                </a>
            @endif
        </div>
    </x-slot>

    @push('styles')
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/themes/dark.css">
        <style>
            .flatpickr-calendar {
                box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            }
            .flatpickr-day.selected.startRange,
            .flatpickr-day.startRange.startRange,
            .flatpickr-day.selected.endRange,
            .flatpickr-day.endRange.endRange {
                background: #4f46e5 !important;
                border-color: #4f46e5 !important;
            }
            .flatpickr-day.inRange {
                background: #c7d2fe !important;
                border-color: #c7d2fe !important;
                box-shadow: -5px 0 0 #c7d2fe, 5px 0 0 #c7d2fe;
            }
            .dark .flatpickr-day.inRange {
                background: #3730a3 !important;
                border-color: #3730a3 !important;
                box-shadow: -5px 0 0 #3730a3, 5px 0 0 #3730a3;
            }
        </style>
    @endpush

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">

            {{-- Mensajes de éxito/error --}}
            @if(session('success'))
                <div
                    class="mb-4 bg-green-100 dark:bg-green-900 border border-green-400 dark:border-green-600 text-green-700 dark:text-green-300 px-4 py-3 rounded flex items-center">
                    <x-icons.check class="w-5 h-5 mr-2" /> {{ session('success') }}
                </div>
            @endif

            @if($errors->any())
                <div
                    class="mb-4 bg-red-100 dark:bg-red-900 border border-red-400 dark:border-red-600 text-red-700 dark:text-red-300 px-4 py-3 rounded">
                    @foreach($errors->all() as $error)
                        <p class="flex items-center"><x-icons.warning class="w-5 h-5 mr-2" /> {{ $error }}</p>
                    @endforeach
                </div>
            @endif

            {{-- Selector de año --}}
            <div class="mb-6 flex gap-2 items-center">
                <span class="text-gray-600 dark:text-gray-400 text-sm">Año:</span>
                @foreach($availableYears as $y)
                    <a href="{{ route('vacation.index', ['year' => $y]) }}"
                        class="px-3 py-1 rounded {{ $y == $year ? 'bg-indigo-600 text-white' : 'bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-300 dark:hover:bg-gray-600' }}">
                        {{ $y }}
                    </a>
                @endforeach
            </div>

            {{-- Información sobre la política de vacaciones --}}
            <div class="mb-6 bg-blue-50 dark:bg-blue-900 border border-blue-200 dark:border-blue-700 rounded-lg p-4">
                <h3 class="font-semibold text-blue-800 dark:text-blue-200 mb-2 flex items-center">
                    <x-icons.list class="w-5 h-5 mr-2" /> Política de Vacaciones
                </h3>
                <ul class="text-sm text-blue-700 dark:text-blue-300 space-y-1">
                    <li>• Cada colaborador tiene derecho a <strong>30 días</strong> de vacaciones por año (equivalente a un mes calendario).</li>
                    <li>• Solo se cuentan <strong>máximo 4 fines de semana</strong> (8 días) como parte de las vacaciones.</li>
                    <li>• Si ya se consumieron los 4 fines de semana, los fines de semana adicionales no se descuentan.</li>
                    <li>• Se pueden asignar períodos variables que en total sumen 30 días efectivos.</li>
                    <li>• Los períodos no pueden solaparse entre sí.</li>
                </ul>
            </div>

            {{-- Formulario de asignación --}}
            @if(Auth::user()->canManageAssignments())
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg mb-6">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4 flex items-center">
                            <x-icons.vacation class="w-6 h-6 mr-2" /> Asignar Vacaciones
                        </h3>

                        <form action="{{ route('vacation.store') }}" method="POST" class="space-y-4">
                            @csrf
                            <input type="hidden" name="year" value="{{ $year }}">
                            <input type="hidden" name="start_date" id="start_date">
                            <input type="hidden" name="end_date" id="end_date">

                            @php
                                $usersForAutocomplete = $teamMembers->map(function($member) use ($userSummaries) {
                                    $summary = $userSummaries[$member->id];
                                    return [
                                        'id' => $member->id,
                                        'name' => $member->name,
                                        'last_name' => $member->last_name,
                                        'work_area' => Auth::user()->isAdmin() ? $member->work_area : null,
                                        'available_days' => $summary['available_days'],
                                        'used_days' => $summary['used_days'],
                                        'weekend_days_used' => $summary['weekend_days_used'],
                                        'remaining_weekend_quota' => $summary['remaining_weekend_quota']
                                    ];
                                })->values()->toArray();
                            @endphp

                            <div>
                                <x-forms.autocomplete 
                                    label="Empleado"
                                    name="user_id"
                                    :items="$usersForAutocomplete"
                                    itemText="name"
                                    itemValue="id"
                                    placeholder="Buscar empleado..."
                                    required="true"
                                />
                            </div>

                            <div id="days-info" class="hidden p-3 bg-indigo-50 dark:bg-indigo-900 rounded-lg space-y-2">
                                <p class="text-sm text-indigo-800 dark:text-indigo-200 flex items-center">
                                    <x-icons.chart class="w-4 h-4 mr-2" />
                                    <span class="font-semibold mr-1">Días disponibles:</span>
                                    <span id="available-days-count">0</span> de 30 días
                                </p>
                                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2.5">
                                    <div id="progress-bar" class="bg-indigo-600 h-2.5 rounded-full" style="width: 0%"></div>
                                </div>
                                <p class="text-xs text-indigo-600 dark:text-indigo-300 flex items-center">
                                    <x-icons.calendar class="w-3 h-3 mr-1" />
                                    Fines de semana: <span id="weekend-used">0</span>/8 usados 
                                    (<span id="weekend-remaining">8</span> restantes)
                                </p>
                            </div>

                            <div>
                                <x-input-label for="date_range" value="Período de Vacaciones" />
                                <input type="text" id="date_range" readonly placeholder="Primero selecciona un empleado..."
                                    class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400 flex items-center">
                                    <x-icons.calendar class="w-3 h-3 mr-1" />
                                    Haz clic para seleccionar el día inicial y el día final en el calendario
                                </p>
                            </div>

                            <div id="period-preview" class="hidden p-3 bg-green-50 dark:bg-green-900 rounded-lg">
                                <div class="flex items-center justify-between">
                                    <p class="text-sm text-green-800 dark:text-green-200 flex items-center">
                                        <x-icons.calendar class="w-4 h-4 mr-1" />
                                        <span class="font-semibold mr-1">Período seleccionado:</span>
                                        <span id="period-text"></span>
                                    </p>
                                    <span id="period-days-badge" class="px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-200 text-green-800 dark:bg-green-800 dark:text-green-200">
                                        <span id="period-days-count">0</span> días
                                    </span>
                                </div>
                            </div>

                            <div id="days-warning" class="hidden p-3 bg-yellow-50 dark:bg-yellow-900 rounded-lg">
                                <p class="text-sm text-yellow-800 dark:text-yellow-200 flex items-center">
                                    <x-icons.warning class="w-5 h-5 mr-2" /> <span id="warning-text"></span>
                                </p>
                            </div>

                            <div>
                                <x-input-label for="notes" value="Notas (opcional)" />
                                <textarea name="notes" id="notes" rows="2" placeholder="Observaciones adicionales..."
                                    class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"></textarea>
                            </div>

                            <div class="pt-4">
                                <x-primary-button class="w-full justify-center" id="submitBtn" disabled>
                                    <x-icons.check class="w-5 h-5 mr-2" />
                                    Asignar Vacaciones
                                </x-primary-button>
                            </div>
                        </form>
                    </div>
                </div>
            @endif

            {{-- Lista de asignaciones actuales --}}
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4 flex items-center">
                        <x-icons.list class="w-5 h-5 mr-2" /> Vacaciones Asignadas - {{ $year }}
                    </h3>

                    @php
                        // Agrupar asignaciones por usuario
                        $assignmentsByUser = $assignments->groupBy('user_id');
                    @endphp

                    @if($assignments->count() > 0)
                        <div class="space-y-4">
                            @foreach($assignmentsByUser as $userId => $userAssignments)
                                @php 
                                    $user = $userAssignments->first()->user;
                                    $summary = $userSummaries[$userId] ?? null;
                                @endphp
                                <div class="border border-gray-200 dark:border-gray-600 rounded-lg overflow-hidden">
                                    {{-- Header del usuario --}}
                                    <div class="bg-gray-100 dark:bg-gray-700 px-4 py-3 flex items-center justify-between">
                                        <div class="flex items-center gap-3">
                                            <div class="w-10 h-10 rounded-full bg-indigo-500 flex items-center justify-center text-white font-semibold">
                                                {{ strtoupper(substr($user->name, 0, 1)) }}{{ strtoupper(substr($user->last_name, 0, 1)) }}
                                            </div>
                                            <div>
                                                <h4 class="font-medium text-gray-900 dark:text-gray-100">
                                                    {{ $user->name }} {{ $user->last_name }}
                                                </h4>
                                                @if(Auth::user()->isAdmin())
                                                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ $user->work_area }}</p>
                                                @endif
                                            </div>
                                        </div>
                                        @if($summary)
                                            <div class="text-right">
                                                <span class="text-sm font-medium {{ $summary['available_days'] > 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                                    {{ $summary['available_days'] }} días disponibles
                                                </span>
                                                <div class="w-24 bg-gray-200 dark:bg-gray-600 rounded-full h-1.5 mt-1">
                                                    <div class="bg-indigo-600 h-1.5 rounded-full" style="width: {{ $summary['percentage_used'] }}%"></div>
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                    
                                    {{-- Lista de períodos asignados --}}
                                    <div class="divide-y divide-gray-200 dark:divide-gray-600">
                                        @foreach($userAssignments->sortBy('start_date') as $assignment)
                                            <div class="px-4 py-3 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-2 bg-white dark:bg-gray-800">
                                                <div class="flex-1">
                                                    <div class="flex items-center gap-2 flex-wrap">
                                                        <span class="px-2 py-0.5 text-xs rounded-full {{ $assignment->status_class }}">
                                                            {{ $assignment->status_label }}
                                                        </span>
                                                        <span class="text-sm text-gray-700 dark:text-gray-300 flex items-center">
                                                            <x-icons.calendar class="w-4 h-4 mr-1" /> {{ $assignment->formatted_period }}
                                                        </span>
                                                    </div>
                                                    <p class="text-xs text-indigo-600 dark:text-indigo-400 mt-1">
                                                        <strong>{{ $assignment->calendar_days }}</strong> días calendario
                                                        <span class="text-gray-500 dark:text-gray-400">
                                                            ({{ $assignment->weekdays }} hábiles + {{ $assignment->weekend_days_count }} fin de semana)
                                                        </span>
                                                    </p>
                                                    @if($assignment->notes)
                                                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 flex items-center">
                                                            <x-icons.edit class="w-3 h-3 mr-1" /> {{ $assignment->notes }}
                                                        </p>
                                                    @endif
                                                </div>

                                                @if(Auth::user()->canManageAssignments() && (Auth::user()->isAdmin() || $assignment->user->work_area === Auth::user()->work_area))
                                                    <div class="flex gap-1">
                                                        <button type="button"
                                                            onclick="openEditModal({{ $assignment->id }}, '{{ $assignment->start_date->format('Y-m-d') }}', '{{ $assignment->end_date->format('Y-m-d') }}', '{{ addslashes($assignment->notes ?? '') }}')"
                                                            class="text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300 p-1.5 hover:bg-gray-100 dark:hover:bg-gray-700 rounded">
                                                            <x-icons.edit class="w-4 h-4" />
                                                        </button>
                                                        <form action="{{ route('vacation.destroy', $assignment) }}" method="POST" class="inline">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit"
                                                                class="text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300 p-1.5 hover:bg-gray-100 dark:hover:bg-gray-700 rounded"
                                                                onclick="return confirm('¿Eliminar estas vacaciones?')">
                                                                <x-icons.delete class="w-4 h-4" />
                                                            </button>
                                                        </form>
                                                    </div>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-8">
                            <x-icons.vacation class="mx-auto h-12 w-12 text-gray-400" />
                            <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-gray-100">No hay vacaciones
                                asignadas</h3>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                Aún no se han programado vacaciones para este año.
                            </p>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Resumen por empleado --}}
            <div class="mt-6 bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4 flex items-center">
                        <x-icons.chart class="w-5 h-5 mr-2" /> Resumen por Empleado
                    </h3>

                    <div class="space-y-3">
                        @foreach($teamMembers as $member)
                            @php $summary = $userSummaries[$member->id]; @endphp
                            <div class="p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                <div class="flex justify-between items-center mb-2">
                                    <span class="font-medium text-gray-800 dark:text-gray-200">
                                        {{ $member->name }} {{ $member->last_name }}
                                        @if(Auth::user()->isAdmin())
                                            <span class="text-xs text-gray-500">[{{ $member->work_area }}]</span>
                                        @endif
                                    </span>
                                    <span
                                        class="text-sm {{ $summary['available_days'] > 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                        {{ $summary['available_days'] }} días disponibles
                                    </span>
                                </div>
                                <div class="w-full bg-gray-200 dark:bg-gray-600 rounded-full h-2">
                                    <div class="bg-indigo-600 h-2 rounded-full transition-all"
                                        style="width: {{ $summary['percentage_used'] }}%"></div>
                                </div>
                                <div class="flex justify-between text-xs text-gray-500 dark:text-gray-400 mt-1">
                                    <span>{{ $summary['used_days'] }} de 30 días usados ({{ $summary['percentage_used'] }}%)</span>
                                    <span class="flex items-center"><x-icons.calendar class="w-3 h-3 mr-1" /> Fines de semana: {{ $summary['weekend_days_used'] }}/8</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

        </div>
    </div>

    {{-- Modal de edición --}}
    <div id="editModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-5 border w-full max-w-md shadow-lg rounded-md bg-white dark:bg-gray-800">
            <div class="mt-3">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4 flex items-center">
                    <x-icons.edit class="w-5 h-5 mr-2" /> Editar Vacaciones
                </h3>
                <form id="editForm" method="POST">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="start_date" id="edit_start_date">
                    <input type="hidden" name="end_date" id="edit_end_date">

                    <div class="space-y-4">
                        <div>
                            <x-input-label for="edit_date_range" value="Período de Vacaciones" />
                            <input type="text" id="edit_date_range" readonly
                                class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm cursor-pointer">
                        </div>

                        <div>
                            <x-input-label for="edit_notes" value="Notas (opcional)" />
                            <textarea name="notes" id="edit_notes" rows="2"
                                class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"></textarea>
                        </div>
                    </div>

                    <div class="flex justify-end gap-3 mt-6">
                        <button type="button" onclick="closeEditModal()"
                            class="px-4 py-2 bg-gray-300 dark:bg-gray-600 text-gray-800 dark:text-gray-200 rounded-md hover:bg-gray-400 dark:hover:bg-gray-500">
                            Cancelar
                        </button>
                        <x-primary-button>
                            Guardar Cambios
                        </x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
        <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/es.js"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                // const userSelect = document.getElementById('user_id'); // Ya no existe el select
                const dateRangeInput = document.getElementById('date_range');
                const startDateInput = document.getElementById('start_date');
                const endDateInput = document.getElementById('end_date');
                const daysInfo = document.getElementById('days-info');
                const availableSpan = document.getElementById('available-days-count');
                const progressBar = document.getElementById('progress-bar');
                const periodPreview = document.getElementById('period-preview');
                const periodText = document.getElementById('period-text');
                const periodDaysCount = document.getElementById('period-days-count');
                const daysWarning = document.getElementById('days-warning');
                const warningText = document.getElementById('warning-text');
                const submitBtn = document.getElementById('submitBtn');
                const weekendUsedSpan = document.getElementById('weekend-used');
                const weekendRemainingSpan = document.getElementById('weekend-remaining');

                let availableDays = 0;
                let weekendRemaining = 8;
                let fp = null;

                // Función para contar fines de semana en un rango
                function countWeekendDays(start, end) {
                    let count = 0;
                    let current = new Date(start);
                    while (current <= end) {
                        const day = current.getDay();
                        if (day === 0 || day === 6) { // Domingo o Sábado
                            count++;
                        }
                        current.setDate(current.getDate() + 1);
                    }
                    return count;
                }

                // Función para contar días hábiles en un rango
                function countWeekdays(start, end) {
                    let count = 0;
                    let current = new Date(start);
                    while (current <= end) {
                        const day = current.getDay();
                        if (day !== 0 && day !== 6) { // Lunes a Viernes
                            count++;
                        }
                        current.setDate(current.getDate() + 1);
                    }
                    return count;
                }

                // Inicializar Flatpickr en modo rango
                fp = flatpickr(dateRangeInput, {
                    locale: 'es',
                    mode: 'range',
                    dateFormat: 'Y-m-d',
                    altInput: true,
                    altFormat: 'j M Y',
                    minDate: 'today',
                    disableMobile: true,
                    allowInput: false,
                    theme: document.documentElement.classList.contains('dark') ? 'dark' : 'light',
                    onChange: function(selectedDates, dateStr, instance) {
                        if (selectedDates.length === 2) {
                            const start = selectedDates[0];
                            const end = selectedDates[1];
                            
                            // Calcular días
                            const calendarDays = Math.ceil(Math.abs(end - start) / (1000 * 60 * 60 * 24)) + 1;
                            const weekendDaysInPeriod = countWeekendDays(start, end);
                            const weekdaysInPeriod = countWeekdays(start, end);
                            
                            // Calcular días efectivos considerando límite de fines de semana
                            const weekendDaysToCount = Math.min(weekendDaysInPeriod, weekendRemaining);
                            const effectiveDays = weekdaysInPeriod + weekendDaysToCount;
                            
                            // Actualizar inputs ocultos
                            startDateInput.value = formatDate(start);
                            endDateInput.value = formatDate(end);
                            
                            // Mostrar preview
                            const startStr = start.toLocaleDateString('es-ES', { day: 'numeric', month: 'short', year: 'numeric' });
                            const endStr = end.toLocaleDateString('es-ES', { day: 'numeric', month: 'short', year: 'numeric' });
                            periodText.textContent = `Del ${startStr} al ${endStr}`;
                            
                            // Mostrar desglose
                            let daysText = `${calendarDays} días calendario`;
                            if (weekendDaysInPeriod > weekendDaysToCount) {
                                const notCounted = weekendDaysInPeriod - weekendDaysToCount;
                                daysText += ` → ${effectiveDays} días efectivos (${notCounted} fin de semana no cuenta)`;
                            } else {
                                daysText += ` (${effectiveDays} efectivos)`;
                            }
                            periodDaysCount.textContent = daysText;
                            periodPreview.classList.remove('hidden');
                            
                            // Validar contra días disponibles
                            if (effectiveDays > availableDays) {
                                warningText.textContent = `El período consume ${effectiveDays} días efectivos pero solo hay ${availableDays} disponibles`;
                                daysWarning.classList.remove('hidden');
                                submitBtn.disabled = true;
                            } else {
                                daysWarning.classList.add('hidden');
                                submitBtn.disabled = false;
                            }
                        } else {
                            // Solo una fecha seleccionada o ninguna
                            startDateInput.value = '';
                            endDateInput.value = '';
                            periodPreview.classList.add('hidden');
                            daysWarning.classList.add('hidden');
                            submitBtn.disabled = true;
                        }
                    }
                });

                function formatDate(date) {
                    const year = date.getFullYear();
                    const month = String(date.getMonth() + 1).padStart(2, '0');
                    const day = String(date.getDate()).padStart(2, '0');
                    return `${year}-${month}-${day}`;
                }

                // Escuchar evento de selección del autocomplete
                document.addEventListener('item-selected', function(e) {
                    const selected = e.detail;
                    
                    // Limpiar selección anterior
                    fp.clear();
                    startDateInput.value = '';
                    endDateInput.value = '';
                    periodPreview.classList.add('hidden');
                    daysWarning.classList.add('hidden');
                    submitBtn.disabled = true;
                    
                    if (selected) {
                        availableDays = parseInt(selected.available_days);
                        const used = parseInt(selected.used_days);
                        const percentage = (used / 30) * 100;
                        const weekendUsed = parseInt(selected.weekend_days_used) || 0;
                        
                        weekendRemaining = selected.remaining_weekend_quota !== undefined 
                            ? parseInt(selected.remaining_weekend_quota) 
                            : 8;
                        
                        availableSpan.textContent = availableDays;
                        progressBar.style.width = percentage + '%';
                        weekendUsedSpan.textContent = weekendUsed;
                        weekendRemainingSpan.textContent = weekendRemaining;
                        daysInfo.classList.remove('hidden');
                        
                        if (availableDays > 0) {
                            // Habilitar el calendario
                            fp.altInput.disabled = false;
                            fp.altInput.placeholder = 'Haz clic para seleccionar el período...';
                            fp.altInput.classList.remove('opacity-50', 'cursor-not-allowed');
                            fp.altInput.classList.add('cursor-pointer');
                        } else {
                            // Deshabilitar el calendario
                            fp.altInput.disabled = true;
                            fp.altInput.placeholder = 'No hay días disponibles';
                            fp.altInput.classList.add('opacity-50', 'cursor-not-allowed');
                            fp.altInput.classList.remove('cursor-pointer');
                        }
                    } else {
                        daysInfo.classList.add('hidden');
                        fp.altInput.disabled = true;
                        fp.altInput.placeholder = 'Primero selecciona un empleado...';
                        fp.altInput.classList.add('opacity-50', 'cursor-not-allowed');
                        fp.altInput.classList.remove('cursor-pointer');
                        availableDays = 0;
                        weekendRemaining = 8;
                    }
                });

                // Inicializar estado del input de fecha
                if (fp.altInput) {
                    fp.altInput.disabled = true;
                    fp.altInput.placeholder = 'Primero selecciona un empleado...';
                    fp.altInput.classList.add('opacity-50', 'cursor-not-allowed');
                }
            });

            // Modal de edición
            function openEditModal(id, startDate, endDate, notes) {
                document.getElementById('editForm').action = `/vacation/${id}`;
                document.getElementById('edit_start_date').value = startDate;
                document.getElementById('edit_end_date').value = endDate;
                document.getElementById('edit_notes').value = notes;
                document.getElementById('editModal').classList.remove('hidden');
                
                // Inicializar flatpickr del modal de edición
                if (window.editFp) {
                    window.editFp.destroy();
                }
                window.editFp = flatpickr('#edit_date_range', {
                    locale: 'es',
                    mode: 'range',
                    dateFormat: 'Y-m-d',
                    altInput: true,
                    altFormat: 'j M Y',
                    defaultDate: [startDate, endDate],
                    disableMobile: true,
                    theme: document.documentElement.classList.contains('dark') ? 'dark' : 'light',
                    onChange: function(selectedDates) {
                        if (selectedDates.length === 2) {
                            const formatDate = (date) => {
                                const year = date.getFullYear();
                                const month = String(date.getMonth() + 1).padStart(2, '0');
                                const day = String(date.getDate()).padStart(2, '0');
                                return `${year}-${month}-${day}`;
                            };
                            document.getElementById('edit_start_date').value = formatDate(selectedDates[0]);
                            document.getElementById('edit_end_date').value = formatDate(selectedDates[1]);
                        }
                    }
                });
            }

            function closeEditModal() {
                document.getElementById('editModal').classList.add('hidden');
                if (window.editFp) {
                    window.editFp.destroy();
                }
            }

            // Cerrar modal al hacer clic fuera
            document.getElementById('editModal').addEventListener('click', function(e) {
                if (e.target === this) {
                    closeEditModal();
                }
            });
        </script>
    @endpush
</x-app-layout>