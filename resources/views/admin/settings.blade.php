<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                ⚙️ {{ __('Configuración del Sistema') }}
            </h2>
            <a href="{{ route('admin.users.index') }}"
               class="inline-flex items-center px-4 py-2 bg-gray-600 dark:bg-gray-700 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-500 dark:hover:bg-gray-600">
                👥 Gestión de Usuarios
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            
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

            {{-- Formulario de configuración --}}
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-6">
                        ⚙️ Parámetros del Sistema
                    </h3>
                    
                    <form action="{{ route('admin.settings.update') }}" method="POST">
                        @csrf
                        @method('PUT')
                        
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            {{-- Días de Home Office por mes --}}
                            <div class="bg-blue-50 dark:bg-blue-900 p-4 rounded-lg">
                                <label for="max_home_office_days" class="block text-sm font-medium text-blue-800 dark:text-blue-200 mb-2">
                                    🏠 Días Home Office por Mes
                                </label>
                                <input type="number" 
                                       name="max_home_office_days" 
                                       id="max_home_office_days" 
                                       value="{{ old('max_home_office_days', $settings['max_home_office_days']) }}"
                                       min="1" 
                                       max="30"
                                       class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-lg font-bold text-center">
                                <p class="mt-2 text-xs text-blue-600 dark:text-blue-300">
                                    Número máximo de días de home office que puede tener cada empleado por mes.
                                </p>
                            </div>

                            {{-- Personas máximas por día --}}
                            <div class="bg-purple-50 dark:bg-purple-900 p-4 rounded-lg">
                                <label for="max_people_per_day" class="block text-sm font-medium text-purple-800 dark:text-purple-200 mb-2">
                                    👥 Personas Máximas por Día
                                </label>
                                <input type="number" 
                                       name="max_people_per_day" 
                                       id="max_people_per_day" 
                                       value="{{ old('max_people_per_day', $settings['max_people_per_day']) }}"
                                       min="1" 
                                       max="100"
                                       class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-lg font-bold text-center">
                                <p class="mt-2 text-xs text-purple-600 dark:text-purple-300">
                                    Límite de personas que pueden estar en home office el mismo día.
                                </p>
                            </div>

                            {{-- Minutos de trabajo por día --}}
                            <div class="bg-green-50 dark:bg-green-900 p-4 rounded-lg">
                                <label for="daily_work_minutes" class="block text-sm font-medium text-green-800 dark:text-green-200 mb-2">
                                    ⏱️ Minutos de Trabajo por Día
                                </label>
                                <input type="number" 
                                       name="daily_work_minutes" 
                                       id="daily_work_minutes" 
                                       value="{{ old('daily_work_minutes', $settings['daily_work_minutes']) }}"
                                       min="60" 
                                       max="1440"
                                       class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-lg font-bold text-center">
                                <p class="mt-2 text-xs text-green-600 dark:text-green-300">
                                    Minutos de trabajo diario (576 min = 9.6 horas).
                                </p>
                                <p class="mt-1 text-xs text-green-700 dark:text-green-400 font-semibold">
                                    = {{ number_format($settings['daily_work_minutes'] / 60, 1) }} horas
                                </p>
                            </div>
                        </div>

                        {{-- Período de Planificación de Enero --}}
                        <div class="mt-6 p-4 bg-orange-50 dark:bg-orange-900 rounded-lg">
                            <h4 class="text-md font-semibold text-orange-800 dark:text-orange-200 mb-4">
                                📅 Período de Planificación para Enero
                            </h4>
                            <p class="text-sm text-orange-600 dark:text-orange-300 mb-4">
                                Configura los días del mes de enero en los que los empleados pueden planificar sus asignaciones.
                            </p>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                {{-- Día de inicio --}}
                                <div>
                                    <label for="january_planning_start_day" class="block text-sm font-medium text-orange-800 dark:text-orange-200 mb-2">
                                        🟢 Día de Inicio
                                    </label>
                                    <input type="number" 
                                           name="january_planning_start_day" 
                                           id="january_planning_start_day" 
                                           value="{{ old('january_planning_start_day', $settings['january_planning_start_day']) }}"
                                           min="1" 
                                           max="31"
                                           class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-lg font-bold text-center">
                                    <p class="mt-2 text-xs text-orange-600 dark:text-orange-300">
                                        Día del mes de enero donde inicia el período.
                                    </p>
                                </div>

                                {{-- Día de fin --}}
                                <div>
                                    <label for="january_planning_end_day" class="block text-sm font-medium text-orange-800 dark:text-orange-200 mb-2">
                                        🔴 Día de Fin
                                    </label>
                                    <input type="number" 
                                           name="january_planning_end_day" 
                                           id="january_planning_end_day" 
                                           value="{{ old('january_planning_end_day', $settings['january_planning_end_day']) }}"
                                           min="1" 
                                           max="31"
                                           class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-lg font-bold text-center">
                                    <p class="mt-2 text-xs text-orange-600 dark:text-orange-300">
                                        Día del mes de enero donde termina el período.
                                    </p>
                                </div>
                            </div>
                            <p class="mt-4 text-sm text-orange-700 dark:text-orange-400 font-semibold">
                                📌 Período actual: Del {{ str_pad($settings['january_planning_start_day'], 2, '0', STR_PAD_LEFT) }}/01 al {{ str_pad($settings['january_planning_end_day'], 2, '0', STR_PAD_LEFT) }}/01
                            </p>
                        </div>

                        <div class="mt-6 flex justify-end">
                            <x-primary-button>
                                💾 Guardar Configuración
                            </x-primary-button>
                        </div>
                    </form>
                </div>
            </div>

            {{-- Información actual --}}
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4">
                        📊 Resumen de Configuración Actual
                    </h3>
                    
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        Parámetro
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        Valor
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        Última Modificación
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        Modificado Por
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                @foreach($allSettings as $setting)
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-100">
                                            @switch($setting->key)
                                                @case('max_home_office_days')
                                                    🏠 Días Home Office por Mes
                                                    @break
                                                @case('max_people_per_day')
                                                    👥 Personas Máximas por Día
                                                    @break
                                                @case('daily_work_minutes')
                                                    ⏱️ Minutos de Trabajo por Día
                                                    @break
                                                @case('january_planning_start_day')
                                                    📅 Inicio Planificación Enero
                                                    @break
                                                @case('january_planning_end_day')
                                                    📅 Fin Planificación Enero
                                                    @break
                                                @default
                                                    {{ $setting->key }}
                                            @endswitch
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                            <span class="px-3 py-1 bg-gray-100 dark:bg-gray-700 rounded-full font-bold">
                                                {{ $setting->value }}
                                                @if($setting->key === 'daily_work_minutes')
                                                    <span class="text-xs font-normal">({{ number_format($setting->value / 60, 1) }}h)</span>
                                                @elseif($setting->key === 'january_planning_start_day' || $setting->key === 'january_planning_end_day')
                                                    <span class="text-xs font-normal">de enero</span>
                                                @endif
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                            {{ $setting->updated_at->format('d/m/Y H:i') }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                            {{ $setting->updatedBy->name ?? 'Sistema' }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- Ayuda --}}
            <div class="bg-yellow-50 dark:bg-yellow-900 overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-semibold text-yellow-800 dark:text-yellow-200 mb-2">
                    💡 Ayuda
                </h3>
                <ul class="list-disc list-inside text-sm text-yellow-700 dark:text-yellow-300 space-y-1">
                    <li><strong>Días Home Office por Mes:</strong> Cada empleado puede solicitar hasta este número de días de trabajo remoto mensualmente.</li>
                    <li><strong>Personas Máximas por Día:</strong> Se bloquean nuevas asignaciones cuando se alcanza este límite en un día específico.</li>
                    <li><strong>Minutos de Trabajo por Día:</strong> Jornada laboral estándar en minutos. (Ejemplo: 480 min = 8 horas, 576 min = 9.6 horas)</li>
                    <li><strong>Período de Planificación de Enero:</strong> Define los días del mes de enero en los que los empleados pueden realizar sus planificaciones. Para el resto de los meses, el período es la última semana del mes anterior.</li>
                </ul>
            </div>
        </div>
    </div>
</x-app-layout>
