<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Dashboard') }} - {{ Carbon\Carbon::create($currentYear, $currentMonth, 1)->locale('es')->monthName }}
            {{ $currentYear }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            {{-- Bienvenida --}}
            <x-dashboard.welcome-card :user="$user" :planningPeriod="$planningPeriod" />

            {{-- Resumen personal --}}
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                <x-dashboard.stat-card
                    icon="home"
                    iconBg="blue"
                    label="Mis días Home Office"
                    :value="$myHomeOfficeDays . ' / ' . $maxHomeOfficeDays"
                />

                <x-dashboard.stat-card
                    icon="clock"
                    iconBg="green"
                    label="Mi horario"
                    :value="$myFlexibleSchedule ? substr($myFlexibleSchedule->start_time, 0, 5) : '08:00'"
                />

                @if($user->canManageAssignments())
                    <x-dashboard.stat-card
                        icon="users"
                        iconBg="purple"
                        label="Home Office hoy"
                        :value="$teamHomeOfficeToday->count()"
                    />

                    <x-dashboard.stat-card
                        icon="clipboard"
                        iconBg="orange"
                        label="Horarios flexibles"
                        :value="$teamFlexibleCount"
                    />
                @else
                    {{-- Para usuarios normales, próximo home office --}}
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-4 col-span-2">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full bg-purple-100 dark:bg-purple-800 mr-3">
                                <svg class="w-5 h-5 text-purple-600 dark:text-purple-300" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z">
                                    </path>
                                </svg>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 dark:text-gray-400">Próximo Home Office</p>
                                <p class="text-lg font-bold text-gray-900 dark:text-gray-100">
                                    @if($nextHomeOffice)
                                        {{ $nextHomeOffice->date->locale('es')->isoFormat('dddd D [de] MMMM') }}
                                    @else
                                        Sin asignar
                                    @endif
                                </p>
                            </div>
                        </div>
                    </div>
                @endif
            </div>

            {{-- Calendario de Home Office (visible para todos) --}}
            <x-dashboard.home-office-calendar
                :user="$user"
                :currentYear="$currentYear"
                :currentMonth="$currentMonth"
                :homeOfficeAssignments="$homeOfficeAssignments"
            />

            {{-- Horarios Flexibles del mes (solo managers/admin) --}}
            @if($user->canManageAssignments())
                <x-dashboard.flexible-schedule-grid
                    :currentYear="$currentYear"
                    :currentMonth="$currentMonth"
                    :flexibleAssignments="$flexibleAssignments"
                />
            @endif

            {{-- Accesos rápidos --}}
            <x-dashboard.quick-access :user="$user" />

        </div>
    </div>

    {{-- Modal para Home Office --}}
    <x-dashboard.modal
        id="dayModal"
        titleId="modalTitle"
        contentId="modalContent"
        closeFunction="closeDayModal"
        linkId="modalLink"
        linkText="Ver todas las asignaciones →"
        linkColor="blue"
    />

    {{-- Modal para Horarios Flexibles --}}
    <x-dashboard.modal
        id="flexibleModal"
        titleId="flexibleModalTitle"
        contentId="flexibleModalContent"
        closeFunction="closeFlexibleModal"
        :linkHref="route('flexible-schedule.index')"
        linkText="Gestionar horarios flexibles →"
        linkColor="green"
    />

    {{-- Scripts --}}
    <x-dashboard.scripts
        :homeOfficeByDate="$homeOfficeByDate"
        :flexibleByArea="$flexibleByArea"
    />

</x-app-layout>
