<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('Horario Flexible') }} - {{ Carbon\Carbon::create($year, $month, 1)->locale('es')->monthName }} {{ $year }}
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

            {{-- Alerta si el área no puede tener horario flexible --}}
            @if(!$areaCanHaveFlexible)
                <div class="mb-4 bg-yellow-100 dark:bg-yellow-900 border border-yellow-400 dark:border-yellow-600 text-yellow-700 dark:text-yellow-300 px-4 py-3 rounded">
                    <p class="font-semibold">⚠️ Tu área ({{ Auth::user()->work_area }}) no puede acceder al horario flexible.</p>
                    <p class="text-sm">Las áreas de Servicio al Cliente, Ventas, Facturación y Almacén no tienen acceso a esta funcionalidad.</p>
                </div>
            @endif

            {{-- Información del período de planificación --}}
            <div class="mb-4 p-4 rounded-lg {{ $planningPeriod['isActive'] ? 'bg-green-100 dark:bg-green-900' : 'bg-yellow-100 dark:bg-yellow-900' }}">
                <p class="text-sm {{ $planningPeriod['isActive'] ? 'text-green-800 dark:text-green-200' : 'text-yellow-800 dark:text-yellow-200' }}">
                    <span class="font-semibold">📅 {{ $planningPeriod['message'] }}</span>
                </p>
            </div>

            {{-- Navegación de meses --}}
            <div class="mb-6 flex justify-between items-center">
                @php
                    $prevMonth = Carbon\Carbon::create($year, $month, 1)->subMonth();
                    $nextMonth = Carbon\Carbon::create($year, $month, 1)->addMonth();
                @endphp
                <a href="{{ route('flexible-schedule.index', ['month' => $prevMonth->month, 'year' => $prevMonth->year]) }}" 
                   class="inline-flex items-center px-4 py-2 bg-gray-200 dark:bg-gray-700 rounded-md text-sm font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-300">
                    ← {{ $prevMonth->locale('es')->monthName }}
                </a>
                <span class="text-lg font-semibold text-gray-800 dark:text-gray-200">
                    {{ Carbon\Carbon::create($year, $month, 1)->locale('es')->monthName }} {{ $year }}
                </span>
                <a href="{{ route('flexible-schedule.index', ['month' => $nextMonth->month, 'year' => $nextMonth->year]) }}" 
                   class="inline-flex items-center px-4 py-2 bg-gray-200 dark:bg-gray-700 rounded-md text-sm font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-300">
                    {{ $nextMonth->locale('es')->monthName }} →
                </a>
            </div>

            {{-- Información de horarios --}}
            <div class="mb-6 bg-blue-50 dark:bg-blue-900 p-4 rounded-lg">
                <p class="text-blue-800 dark:text-blue-200">
                    <span class="font-semibold">⏰ Horarios de ingreso disponibles:</span> 
                    @foreach($allowedTimes as $time)
                        <span class="inline-block bg-blue-200 dark:bg-blue-700 px-2 py-1 rounded mx-1">{{ $time }}</span>
                    @endforeach
                </p>
            </div>

            @if(Auth::user()->canManageAssignments() && $areaCanHaveFlexible && ($planningPeriod['isActive'] || Auth::user()->isAdmin()))
            {{-- Formulario de asignación --}}
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4">
                        Asignar Horario Flexible
                        @if(Auth::user()->isAdmin() && !$planningPeriod['isActive'])
                            <span class="text-sm font-normal text-yellow-600">(Modo Admin - fuera de período)</span>
                        @endif
                    </h3>
                    
                    <form action="{{ route('flexible-schedule.store') }}" method="POST" class="grid grid-cols-1 md:grid-cols-5 gap-4">
                        @csrf
                        
                        <div>
                            <x-input-label for="user_id" value="Empleado" />
                            <select name="user_id" id="user_id" required
                                    class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                <option value="">Seleccionar...</option>
                                @foreach($teamMembers as $member)
                                    <option value="{{ $member->id }}">{{ $member->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        
                        <div>
                            <x-input-label for="month" value="Mes" />
                            <select name="month" id="month" required
                                    class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                @for($m = 1; $m <= 12; $m++)
                                    <option value="{{ $m }}" {{ $m == $month ? 'selected' : '' }}>
                                        {{ Carbon\Carbon::create(null, $m, 1)->locale('es')->monthName }}
                                    </option>
                                @endfor
                            </select>
                        </div>
                        
                        <div>
                            <x-input-label for="year" value="Año" />
                            <select name="year" id="year" required
                                    class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                @for($y = now()->year; $y <= now()->year + 1; $y++)
                                    <option value="{{ $y }}" {{ $y == $year ? 'selected' : '' }}>{{ $y }}</option>
                                @endfor
                            </select>
                        </div>
                        
                        <div>
                            <x-input-label for="start_time" value="Horario de Entrada" />
                            <select name="start_time" id="start_time" required
                                    class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                @foreach($allowedTimes as $time)
                                    <option value="{{ $time }}">{{ $time }}</option>
                                @endforeach
                            </select>
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

            {{-- Listado de asignaciones --}}
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4">
                        Asignaciones de {{ Carbon\Carbon::create($year, $month, 1)->locale('es')->monthName }} {{ $year }}
                    </h3>
                    
                    @if($assignments->isEmpty())
                        <p class="text-gray-500 dark:text-gray-400">No hay asignaciones de horario flexible para este mes.</p>
                    @else
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-700">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                            Empleado
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                            Área
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                            Horario de Entrada
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                            Asignado por
                                        </th>
                                        @if(Auth::user()->canManageAssignments())
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                            Acciones
                                        </th>
                                        @endif
                                    </tr>
                                </thead>
                                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                    @foreach($assignments as $assignment)
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-100">
                                                {{ $assignment->user->name }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                                {{ $assignment->user->work_area }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="px-3 py-1 rounded-full text-sm font-semibold
                                                    {{ substr($assignment->start_time, 0, 5) == '08:00' ? 'bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100' : '' }}
                                                    {{ substr($assignment->start_time, 0, 5) == '08:30' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-800 dark:text-yellow-100' : '' }}
                                                    {{ substr($assignment->start_time, 0, 5) == '09:00' ? 'bg-blue-100 text-blue-800 dark:bg-blue-800 dark:text-blue-100' : '' }}">
                                                    {{ substr($assignment->start_time, 0, 5) }}
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                                {{ $assignment->assignedBy->name }}
                                            </td>
                                            @if(Auth::user()->canManageAssignments())
                                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                                @if(Auth::user()->isAdmin() || $assignment->user->work_area === Auth::user()->work_area)
                                                    <div class="flex space-x-2">
                                                        {{-- Botón editar (cambiar horario) --}}
                                                        <button type="button" 
                                                                onclick="openEditModal({{ $assignment->id }}, '{{ substr($assignment->start_time, 0, 5) }}')"
                                                                class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300">
                                                            Editar
                                                        </button>
                                                        
                                                        {{-- Formulario eliminar --}}
                                                        <form action="{{ route('flexible-schedule.destroy', $assignment) }}" method="POST" class="inline">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" 
                                                                    class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300"
                                                                    onclick="return confirm('¿Eliminar esta asignación?')">
                                                                Eliminar
                                                            </button>
                                                        </form>
                                                    </div>
                                                @endif
                                            </td>
                                            @endif
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Resumen por horario --}}
            <div class="mt-6 grid grid-cols-1 md:grid-cols-3 gap-4">
                @foreach($allowedTimes as $time)
                    @php
                        $countForTime = $assignments->filter(fn($a) => substr($a->start_time, 0, 5) === $time)->count();
                    @endphp
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-4">
                        <div class="text-center">
                            <div class="text-2xl font-bold 
                                {{ $time == '08:00' ? 'text-green-600 dark:text-green-400' : '' }}
                                {{ $time == '08:30' ? 'text-yellow-600 dark:text-yellow-400' : '' }}
                                {{ $time == '09:00' ? 'text-blue-600 dark:text-blue-400' : '' }}">
                                {{ $countForTime }}
                            </div>
                            <div class="text-gray-600 dark:text-gray-400">
                                Entrada a las {{ $time }}
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
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
                        <select name="start_time" id="edit_start_time" required
                                class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                            @foreach($allowedTimes as $time)
                                <option value="{{ $time }}">{{ $time }}</option>
                            @endforeach
                        </select>
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
        function openEditModal(id, currentTime) {
            document.getElementById('editForm').action = '/flexible-schedule/' + id;
            document.getElementById('edit_start_time').value = currentTime;
            document.getElementById('editModal').classList.remove('hidden');
        }
        
        function closeEditModal() {
            document.getElementById('editModal').classList.add('hidden');
        }
    </script>
</x-app-layout>
