<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('Home Office') }} - {{ Carbon\Carbon::create($year, $month, 1)->locale('es')->monthName }}
                {{ $year }}
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
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            {{-- Mensajes de éxito/error --}}
            @if(session('success'))
                <div class="mb-4 bg-green-100 dark:bg-green-900 border border-green-400 dark:border-green-600 text-green-700 dark:text-green-300 px-4 py-3 rounded">
                    {{ session('success') }}
                </div>
            @endif

            @if($errors->any())
                <div class="mb-4 bg-red-100 dark:bg-red-900 border border-red-400 dark:border-red-600 text-red-700 dark:text-red-300 px-4 py-3 rounded">
                    @foreach($errors->all() as $error)
                        <p>{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            {{-- Información del período de planificación --}}
            <div
                class="mb-4 p-4 rounded-lg {{ $planningPeriod['isActive'] ? 'bg-green-100 dark:bg-green-900' : 'bg-yellow-100 dark:bg-yellow-900' }}">
                <p
                    class="text-sm {{ $planningPeriod['isActive'] ? 'text-green-800 dark:text-green-200' : 'text-yellow-800 dark:text-yellow-200' }}">
                    <span class="font-semibold">📅 {{ $planningPeriod['message'] }}</span>
                </p>
            </div>

            {{-- Navegación de meses --}}
            <div class="mb-6 flex justify-between items-center">
                @php
                    $prevMonth = Carbon\Carbon::create($year, $month, 1)->subMonth();
                    $nextMonth = Carbon\Carbon::create($year, $month, 1)->addMonth();
                @endphp
                <a href="{{ route('home-office.index', ['month' => $prevMonth->month, 'year' => $prevMonth->year]) }}"
                    class="inline-flex items-center px-4 py-2 bg-gray-200 dark:bg-gray-700 rounded-md text-sm font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-300">
                    ← {{ $prevMonth->locale('es')->monthName }}
                </a>
                <span class="text-lg font-semibold text-gray-800 dark:text-gray-200">
                    {{ Carbon\Carbon::create($year, $month, 1)->locale('es')->monthName }} {{ $year }}
                </span>
                <a href="{{ route('home-office.index', ['month' => $nextMonth->month, 'year' => $nextMonth->year]) }}"
                    class="inline-flex items-center px-4 py-2 bg-gray-200 dark:bg-gray-700 rounded-md text-sm font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-300">
                    {{ $nextMonth->locale('es')->monthName }} →
                </a>
            </div>

            {{-- Información de límites --}}
            <div class="mb-6 grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="bg-blue-50 dark:bg-blue-900 p-4 rounded-lg">
                    <p class="text-blue-800 dark:text-blue-200">
                        <span class="font-semibold">📊 Máximo días por persona:</span> {{ $maxDaysPerMonth }} días/mes
                    </p>
                </div>
                <div class="bg-purple-50 dark:bg-purple-900 p-4 rounded-lg">
                    <p class="text-purple-800 dark:text-purple-200">
                        <span class="font-semibold">👥 Máximo personas por día:</span> {{ $maxPeoplePerDay }} personas
                    </p>
                </div>
            </div>

            @if(Auth::user()->canManageAssignments() && ($planningPeriod['isActive'] || Auth::user()->isAdmin()))
                {{-- Formulario de asignación --}}
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg mb-6">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4">
                            Asignar día de Home Office
                            @if(Auth::user()->isAdmin() && !$planningPeriod['isActive'])
                                <span class="text-sm font-normal text-yellow-600">(Modo Admin - fuera de período)</span>
                            @endif
                        </h3>

                        <form action="{{ route('home-office.store') }}" method="POST"
                            class="grid grid-cols-1 md:grid-cols-4 gap-4">
                            @csrf

                            <div>
                                <x-input-label for="user_id" value="Empleado" />
                                <select name="user_id" id="user_id" required
                                    class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                    <option value="">Seleccionar...</option>
                                    @foreach($teamMembers as $member)
                                        <option value="{{ $member->id }}">
                                            {{ $member->name }}
                                            ({{ $member->homeOfficeDaysInMonth($month, $year) }}/{{ $maxDaysPerMonth }} días)
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <x-input-label for="date" value="Fecha" />
                                <input type="date" name="date" id="date" required min="{{ now()->toDateString() }}"
                                    class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                            </div>

                            <div class="flex items-end">
                                <x-primary-button>
                                    Asignar
                                </x-primary-button>
                            </div>
                        </form>
                    </div>
                </div>
            @endif

            {{-- Calendario del mes --}}
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4">Calendario de Home Office
                    </h3>

                    {{-- Encabezados de días --}}
                    <div class="grid grid-cols-7 gap-1 mb-2">
                        @foreach(['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'] as $dayName)
                            <div class="text-center text-sm font-semibold text-gray-600 dark:text-gray-400 py-2">
                                {{ $dayName }}
                            </div>
                        @endforeach
                    </div>

                    {{-- Días del mes --}}
                    <div class="grid grid-cols-7 gap-1">
                        {{-- Espacios vacíos para el primer día --}}
                        @php
                            $firstDayOfWeek = Carbon\Carbon::create($year, $month, 1)->dayOfWeekIso - 1;
                        @endphp
                        @for($i = 0; $i < $firstDayOfWeek; $i++)
                            <div class="min-h-24 bg-gray-50 dark:bg-gray-900 rounded"></div>
                        @endfor

                        {{-- Días del mes --}}
                        @foreach($daysInMonth as $day)
                            <div class="min-h-24 p-2 rounded border 
                                    {{ $day['isWeekend'] ? 'bg-gray-100 dark:bg-gray-900' : 'bg-white dark:bg-gray-800' }}
                                    {{ $day['isToday'] ? 'border-blue-500 border-2' : 'border-gray-200 dark:border-gray-700' }}
                                    {{ $day['isFull'] ? 'bg-red-50 dark:bg-red-900' : '' }}">

                                <div class="flex justify-between items-start mb-1">
                                    <span
                                        class="text-sm font-semibold {{ $day['isToday'] ? 'text-blue-600 dark:text-blue-400' : 'text-gray-700 dark:text-gray-300' }}">
                                        {{ $day['date']->day }}
                                    </span>
                                    @if(!$day['isWeekend'])
                                        <span
                                            class="text-xs px-1 rounded {{ $day['isFull'] ? 'bg-red-200 dark:bg-red-800 text-red-800 dark:text-red-200' : 'bg-green-200 dark:bg-green-800 text-green-800 dark:text-green-200' }}">
                                            {{ $day['available'] }}
                                        </span>
                                    @endif
                                </div>

                                @if(!$day['isWeekend'])
                                    <div class="space-y-1">
                                        @foreach($day['assignments'] as $assignment)
                                            <div
                                                class="text-xs bg-blue-100 dark:bg-blue-800 text-blue-800 dark:text-blue-200 px-1 py-0.5 rounded flex justify-between items-center group">
                                                <span class="truncate" title="{{ $assignment->user->name }}">
                                                    {{ Str::limit($assignment->user->name, 10) }}
                                                </span>
                                                @if(Auth::user()->canManageAssignments() && (Auth::user()->isAdmin() || $assignment->user->work_area === Auth::user()->work_area))
                                                    <form action="{{ route('home-office.destroy', $assignment) }}" method="POST"
                                                        class="hidden group-hover:inline">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="text-red-600 hover:text-red-800 ml-1"
                                                            onclick="return confirm('¿Eliminar esta asignación?')">
                                                            ×
                                                        </button>
                                                    </form>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- Leyenda --}}
            <div class="mt-4 flex flex-wrap gap-4 text-sm text-gray-600 dark:text-gray-400">
                <div class="flex items-center">
                    <span class="w-4 h-4 bg-green-200 dark:bg-green-800 rounded mr-2"></span>
                    Espacios disponibles
                </div>
                <div class="flex items-center">
                    <span class="w-4 h-4 bg-red-50 dark:bg-red-900 border border-red-200 dark:border-red-700 rounded mr-2"></span>
                    Día completo (sin espacios)
                </div>
                <div class="flex items-center">
                    <span class="w-4 h-4 bg-blue-100 dark:bg-blue-800 rounded mr-2"></span>
                    Persona asignada
                </div>
                <div class="flex items-center">
                    <span class="w-4 h-4 border-2 border-blue-500 rounded mr-2"></span>
                    Hoy
                </div>
            </div>
        </div>
    </div>
</x-app-layout>