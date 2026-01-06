<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            {{-- Bienvenida --}}
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <h3 class="text-lg font-semibold mb-2">¡Bienvenido, {{ Auth::user()->name }}!</h3>
                    <p class="text-gray-600 dark:text-gray-400">
                        <span class="font-medium">Área:</span> {{ Auth::user()->work_area }} |
                        <span class="font-medium">Rol:</span>
                        @if(Auth::user()->role === 'admin')
                            <span class="text-red-600 dark:text-red-400">Administrador</span>
                        @elseif(Auth::user()->role === 'manager')
                            <span class="text-blue-600 dark:text-blue-400">Manager</span>
                        @else
                            <span class="text-green-600 dark:text-green-400">Usuario</span>
                        @endif
                    </p>
                </div>
            </div>

            @php
                $currentMonth = now()->month;
                $currentYear = now()->year;
                $user = Auth::user();

                // Estadísticas de Home Office del usuario
                $myHomeOfficeDays = $user->homeOfficeDaysInMonth($currentMonth, $currentYear);
                $maxHomeOfficeDays = App\Models\SystemSetting::getInt('max_home_office_days', 2);

                // Próximo día de home office
                $nextHomeOffice = App\Models\HomeOfficeAssignment::where('user_id', $user->id)
                    ->where('date', '>=', now())
                    ->orderBy('date')
                    ->first();

                // Horario flexible del usuario este mes
                $myFlexibleSchedule = App\Models\FlexibleScheduleAssignment::where('user_id', $user->id)
                    ->where('month', $currentMonth)
                    ->where('year', $currentYear)
                    ->first();

                // Estadísticas para managers
                $teamHomeOfficeToday = null;
                $teamFlexibleCount = null;
                if ($user->canManageAssignments()) {
                    $teamHomeOfficeToday = App\Models\HomeOfficeAssignment::whereHas(
                        'user',
                        fn($q) =>
                        $user->isAdmin() ? true : $q->where('work_area', $user->work_area)
                    )->whereDate('date', now())->with('user')->get();

                    $teamFlexibleCount = App\Models\FlexibleScheduleAssignment::whereHas(
                        'user',
                        fn($q) =>
                        $user->isAdmin() ? true : $q->where('work_area', $user->work_area)
                    )->where('month', $currentMonth)->where('year', $currentYear)->count();
                }
            @endphp

            {{-- Resumen personal --}}
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-6">
                {{-- Home Office este mes --}}
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-blue-100 dark:bg-blue-800 mr-4">
                            <svg class="w-6 h-6 text-blue-600 dark:text-blue-300" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6">
                                </path>
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Home Office este mes</p>
                            <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                                {{ $myHomeOfficeDays }} / {{ $maxHomeOfficeDays }}
                            </p>
                        </div>
                    </div>
                    @if($nextHomeOffice)
                        <p class="mt-3 text-sm text-blue-600 dark:text-blue-400">
                            📅 Próximo: {{ $nextHomeOffice->date->locale('es')->isoFormat('dddd D [de] MMMM') }}
                        </p>
                    @endif
                </div>

                {{-- Horario flexible --}}
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-green-100 dark:bg-green-800 mr-4">
                            <svg class="w-6 h-6 text-green-600 dark:text-green-300" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Horario de entrada</p>
                            <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                                @if($myFlexibleSchedule)
                                    {{ substr($myFlexibleSchedule->start_time, 0, 5) }}
                                @else
                                    08:00
                                @endif
                            </p>
                        </div>
                    </div>
                    <p class="mt-3 text-sm text-gray-500 dark:text-gray-400">
                        @if($myFlexibleSchedule)
                            Horario flexible asignado
                        @else
                            Horario estándar
                        @endif
                    </p>
                </div>

                {{-- Mes actual --}}
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-purple-100 dark:bg-purple-800 mr-4">
                            <svg class="w-6 h-6 text-purple-600 dark:text-purple-300" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z">
                                </path>
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Período actual</p>
                            <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                                {{ now()->locale('es')->monthName }}
                            </p>
                        </div>
                    </div>
                    <p class="mt-3 text-sm text-gray-500 dark:text-gray-400">
                        {{ now()->year }}
                    </p>
                </div>
            </div>

            {{-- Sección para Managers --}}
            @if($user->canManageAssignments())
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg mb-6">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4">
                            👥 Gestión de equipo {{ $user->isAdmin() ? '(Todas las áreas)' : '(' . $user->work_area . ')' }}
                        </h3>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            {{-- Home office hoy --}}
                            <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                                <h4 class="font-medium text-gray-700 dark:text-gray-300 mb-3">
                                    🏠 Home Office hoy ({{ now()->locale('es')->isoFormat('D [de] MMMM') }})
                                </h4>
                                @if($teamHomeOfficeToday && $teamHomeOfficeToday->count() > 0)
                                    <div class="space-y-2">
                                        @foreach($teamHomeOfficeToday as $assignment)
                                            <div class="flex items-center text-sm text-gray-600 dark:text-gray-400">
                                                <span class="w-2 h-2 bg-green-500 rounded-full mr-2"></span>
                                                {{ $assignment->user->name }}
                                                <span class="text-xs text-gray-400 ml-2">({{ $assignment->user->work_area }})</span>
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <p class="text-sm text-gray-500 dark:text-gray-400">No hay nadie en home office hoy.</p>
                                @endif
                            </div>

                            {{-- Horarios flexibles este mes --}}
                            <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                                <h4 class="font-medium text-gray-700 dark:text-gray-300 mb-3">
                                    ⏰ Horarios Flexibles este mes
                                </h4>
                                <p class="text-3xl font-bold text-purple-600 dark:text-purple-400">
                                    {{ $teamFlexibleCount }}
                                </p>
                                <p class="text-sm text-gray-500 dark:text-gray-400">empleados con horario flexible</p>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Accesos rápidos --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <a href="{{ route('home-office.index') }}"
                    class="block bg-blue-50 dark:bg-blue-900 hover:bg-blue-100 dark:hover:bg-blue-800 rounded-lg p-6 transition">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-lg font-semibold text-blue-800 dark:text-blue-200">Home Office</h3>
                            <p class="text-blue-600 dark:text-blue-300 text-sm">Ver calendario y asignaciones</p>
                        </div>
                        <svg class="w-8 h-8 text-blue-600 dark:text-blue-300" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7">
                            </path>
                        </svg>
                    </div>
                </a>

                <a href="{{ route('flexible-schedule.index') }}"
                    class="block bg-green-50 dark:bg-green-900 hover:bg-green-100 dark:hover:bg-green-800 rounded-lg p-6 transition">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-lg font-semibold text-green-800 dark:text-green-200">Horario Flexible</h3>
                            <p class="text-green-600 dark:text-green-300 text-sm">Ver horarios del mes</p>
                        </div>
                        <svg class="w-8 h-8 text-green-600 dark:text-green-300" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7">
                            </path>
                        </svg>
                    </div>
                </a>
            </div>
        </div>
    </div>
</x-app-layout>