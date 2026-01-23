<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('Asignación de Home Office') }} -
                {{ Carbon\Carbon::create($year, $month, 1)->locale('es')->monthName }} {{ $year }}
            </h2>
            @if(Auth::user()->canManageAssignments())
                <a href="{{ route('home-office.report', ['month' => $month, 'year' => $year]) }}"
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

            {{-- Contenido principal según el período de planificación --}}
            @if($planningPeriod['isActive'] || Auth::user()->isAdmin())

                {{-- Período activo - Mostrar formulario de asignación --}}
                @if(Auth::user()->canManageAssignments())
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            {{-- Información del período activo --}}
                            <div class="mb-6 p-4 rounded-lg bg-green-100 dark:bg-green-900">
                                <p class="text-sm text-green-800 dark:text-green-200 flex items-center">
                                    <x-icons.check class="w-5 h-5 mr-2" />
                                    <span class="font-semibold mr-1">Período de planificación activo</span>
                                </p>
                                <p class="text-xs text-green-800 dark:text-green-200 mt-1 ml-7">
                                    Del {{ $planningPeriod['start']->format('d/m/Y') }} al
                                    {{ $planningPeriod['end']->format('d/m/Y') }}
                                </p>
                            </div>

                            <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4">
                                Asignar día de Home Office
                                @if(Auth::user()->isAdmin() && !$planningPeriod['isActive'])
                                    <span class="text-sm font-normal text-yellow-600">(Modo Admin - fuera de período)</span>
                                @endif
                            </h3>

                            {{-- Información de límites --}}
                            <div class="mb-6 grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="bg-blue-50 dark:bg-blue-900 p-3 rounded-lg">
                                    <p class="text-sm text-blue-800 dark:text-blue-200 flex items-center">
                                        <x-icons.chart class="w-4 h-4 mr-2" />
                                        <span class="font-semibold mr-1">Máximo:</span> {{ $maxDaysPerMonth }} días/mes
                                    </p>
                                </div>
                                <div class="bg-purple-50 dark:bg-purple-900 p-3 rounded-lg">
                                    <p class="text-sm text-purple-800 dark:text-purple-200 flex items-center">
                                        <x-icons.users class="w-4 h-4 mr-2" />
                                        <span class="font-semibold mr-1">Por día:</span> {{ $maxPeoplePerDay }} personas
                                    </p>
                                </div>
                            </div>

                            <form action="{{ route('home-office.store') }}" method="POST" class="space-y-4">
                                @csrf

                                    @php
                                        $usersForAutocomplete = $teamMembers->map(function($member) use ($month, $year) {
                                            $daysUsed = $member->homeOfficeDaysInMonth($month, $year);
                                            return [
                                                'id' => $member->id,
                                                'name' => $member->name,
                                                'last_name' => $member->last_name,
                                                'work_area' => Auth::user()->isAdmin() ? $member->work_area : null,
                                                'days_used' => $daysUsed
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

                                <div>
                                    <x-input-label for="dates" value="Fechas de Home Office" />
                                    <input type="text" name="dates" id="dates" required readonly
                                        placeholder="Primero selecciona un empleado..."
                                        class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm cursor-pointer disabled:opacity-50"
                                        disabled>
                                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400 flex items-center">
                                        <x-icons.calendar class="w-3 h-3 mr-1" />
                                        Selecciona días hábiles desde hoy hasta fin de
                                        {{ Carbon\Carbon::create($year, $month, 1)->locale('es')->monthName }}
                                    </p>
                                    <p id="days-available-info"
                                        class="mt-1 text-xs font-medium text-indigo-600 dark:text-indigo-400 hidden">
                                    </p>
                                    <div id="selected-dates-preview" class="mt-2 flex flex-wrap gap-2"></div>
                                </div>

                                <div class="pt-4">
                                    <x-primary-button class="w-full justify-center">
                                        <x-icons.check class="w-5 h-5 mr-2" />
                                        Asignar Home Office
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
                                    @foreach($assignments->sortBy('date') as $assignment)
                                        <div class="flex justify-between items-center p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                            <div>
                                                <span
                                                    class="font-medium text-gray-800 dark:text-gray-200">{{ $assignment->user->name }}</span>
                                                <span class="text-sm text-gray-500 dark:text-gray-400 ml-2">
                                                    {{ Carbon\Carbon::parse($assignment->date)->locale('es')->isoFormat('dddd D [de] MMMM') }}
                                                </span>
                                            </div>
                                            @if(Auth::user()->canManageAssignments() && (Auth::user()->isAdmin() || $assignment->user->work_area === Auth::user()->work_area))
                                                <form action="{{ route('home-office.destroy', $assignment) }}" method="POST">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit"
                                                        class="text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300"
                                                        onclick="return confirm('¿Eliminar esta asignación?')">
                                                        <x-icons.delete class="w-5 h-5" />
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    @endforeach
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
                                No tienes permisos para asignar días de home office.
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
                                    <br>
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
                                    <br>
                                    <span class="text-lg">{{ $nextPeriod['start']->format('d/m/Y') }} -
                                        {{ $nextPeriod['end']->format('d/m/Y') }}</span>
                                </p>
                            </div>

                            <p class="mt-6 text-sm text-gray-500 dark:text-gray-400">
                                Regresa durante el período indicado para realizar las asignaciones de home office.
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
                                    <br>
                                    <span class="text-lg">{{ $planningPeriod['start']->format('d/m/Y') }} -
                                        {{ $planningPeriod['end']->format('d/m/Y') }}</span>
                                </p>
                            </div>

                            <p class="mt-6 text-sm text-gray-500 dark:text-gray-400">
                                Regresa durante el período indicado para realizar las asignaciones de home office.
                            </p>
                        @endif
                    </div>
                </div>
            @endif

        </div>
    </div>

    {{-- Flatpickr CSS y JS para el calendario --}}
    @push('styles')
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/themes/dark.css">
    @endpush

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
        <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/es.js"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const datesInput = document.getElementById('dates');
                const previewContainer = document.getElementById('selected-dates-preview');
                // const userSelect = document.getElementById('user_id'); // Reemplazado por autocomplete
                const daysAvailableInfo = document.getElementById('days-available-info');
                const maxDaysPerMonth = {{ $maxDaysPerMonth }};

                let fp = null;
                let maxSelectableDates = 0;

                if (datesInput) {
                    // Inicializar flatpickr
                    fp = flatpickr(datesInput, {
                        locale: 'es',
                        dateFormat: 'Y-m-d',
                        minDate: '{{ now()->toDateString() }}',
                        maxDate: '{{ Carbon\Carbon::create($year, $month, 1)->endOfMonth()->toDateString() }}',
                        mode: 'multiple',
                        conjunction: ', ',
                        disableMobile: true,
                        clickOpens: false, // Deshabilitado hasta seleccionar empleado
                        theme: document.documentElement.classList.contains('dark') ? 'dark' : 'light',
                        disable: [
                            function (date) {
                                return (date.getDay() === 0 || date.getDay() === 6);
                            }
                        ],
                        onChange: function (selectedDates, dateStr, instance) {
                            // Limitar la cantidad de fechas seleccionadas
                            if (selectedDates.length > maxSelectableDates) {
                                // Remover la última fecha agregada
                                const limitedDates = selectedDates.slice(0, maxSelectableDates);
                                instance.setDate(limitedDates);

                                // Mostrar alerta
                                showLimitAlert();
                                return;
                            }
                            updatePreview(selectedDates, instance);
                        }
                    });

                    // Escuchar selección del autocomplete
                    document.addEventListener('item-selected', function (e) {
                         const selected = e.detail;
                         
                         // Limpiar fechas seleccionadas
                        fp.clear();
                        previewContainer.innerHTML = '';
                        
                        if (selected) {
                            const daysUsed = parseInt(selected.days_used) || 0;
                            maxSelectableDates = maxDaysPerMonth - daysUsed;

                            // Habilitar el selector de fechas
                            datesInput.disabled = false;
                            datesInput.placeholder = 'Selecciona una o más fechas...';
                            fp.set('clickOpens', true);

                            // Mostrar días disponibles
                            if (maxSelectableDates > 0) {
                                daysAvailableInfo.textContent = `✨ Puedes seleccionar hasta ${maxSelectableDates} día(s) para este empleado`;
                                daysAvailableInfo.classList.remove('hidden', 'text-red-600', 'dark:text-red-400');
                                daysAvailableInfo.classList.add('text-indigo-600', 'dark:text-indigo-400');
                            } else {
                                daysAvailableInfo.textContent = `⚠️ Este empleado ya tiene ${maxDaysPerMonth} días asignados (límite alcanzado)`;
                                daysAvailableInfo.classList.remove('hidden', 'text-indigo-600', 'dark:text-indigo-400');
                                daysAvailableInfo.classList.add('text-red-600', 'dark:text-red-400');
                                datesInput.disabled = true;
                                datesInput.placeholder = 'Límite de días alcanzado';
                                fp.set('clickOpens', false);
                            }
                        } else {
                            // Deshabilitar el selector de fechas
                            datesInput.disabled = true;
                            datesInput.placeholder = 'Primero selecciona un empleado...';
                            fp.set('clickOpens', false);
                            daysAvailableInfo.classList.add('hidden');
                            maxSelectableDates = 0;
                        }
                    });

                    // Click en el input abre el calendario si está habilitado
                    datesInput.addEventListener('click', function () {
                        if (!this.disabled && fp) {
                            fp.open();
                        }
                    });

                    function showLimitAlert() {
                        // Crear alerta temporal
                        const alert = document.createElement('div');
                        alert.className = 'fixed top-4 right-4 bg-yellow-100 border border-yellow-400 text-yellow-800 px-4 py-3 rounded-lg shadow-lg z-50 animate-pulse';
                        alert.innerHTML = `
                                <div class="flex items-center">
                                    <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                                    </svg>
                                    <span>Solo puedes seleccionar ${maxSelectableDates} día(s) para este empleado</span>
                                </div>
                            `;
                        document.body.appendChild(alert);

                        setTimeout(() => {
                            alert.remove();
                        }, 3000);
                    }

                    function updatePreview(selectedDates, fpInstance) {
                        previewContainer.innerHTML = '';

                        if (selectedDates.length === 0) {
                            return;
                        }

                        // Ordenar fechas
                        selectedDates.sort((a, b) => a - b);

                        selectedDates.forEach(function (date, index) {
                            const badge = document.createElement('span');
                            badge.className = 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800 dark:bg-indigo-900 dark:text-indigo-200';

                            const dateText = date.toLocaleDateString('es-ES', {
                                weekday: 'short',
                                day: 'numeric',
                                month: 'short'
                            });

                            badge.innerHTML = `
                                        ${dateText}
                                        <button type="button" class="ml-1 inline-flex items-center justify-center w-4 h-4 rounded-full hover:bg-indigo-200 dark:hover:bg-indigo-700" data-index="${index}">
                                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                                            </svg>
                                        </button>
                                    `;

                            badge.querySelector('button').addEventListener('click', function () {
                                const newDates = fpInstance.selectedDates.filter((_, i) => i !== index);
                                fpInstance.setDate(newDates);
                            });

                            previewContainer.appendChild(badge);
                        });

                        // Mostrar contador con límite
                        const counter = document.createElement('span');
                        const isAtLimit = selectedDates.length >= maxSelectableDates;
                        counter.className = `inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${isAtLimit ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200' : 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200'}`;
                        counter.textContent = `${selectedDates.length}/${maxSelectableDates} día(s) seleccionado(s)`;
                        previewContainer.appendChild(counter);
                    }
                }
            });
        </script>
    @endpush
</x-app-layout>