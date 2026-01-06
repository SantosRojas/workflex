@props(['user'])

<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    <a href="{{ route('home-office.index') }}"
        class="block bg-blue-50 dark:bg-blue-900 hover:bg-blue-100 dark:hover:bg-blue-800 rounded-lg p-6 transition">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-lg font-semibold text-blue-800 dark:text-blue-200">Home Office</h3>
                <p class="text-blue-600 dark:text-blue-300 text-sm">
                    {{ $user->canManageAssignments() ? 'Asignar días de home office' : 'Ver mis asignaciones' }}
                </p>
            </div>
            <svg class="w-8 h-8 text-blue-600 dark:text-blue-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
            </svg>
        </div>
    </a>

    <a href="{{ route('flexible-schedule.index') }}"
        class="block bg-green-50 dark:bg-green-900 hover:bg-green-100 dark:hover:bg-green-800 rounded-lg p-6 transition">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-lg font-semibold text-green-800 dark:text-green-200">Horario Flexible</h3>
                <p class="text-green-600 dark:text-green-300 text-sm">
                    {{ $user->canManageAssignments() ? 'Asignar horarios flexibles' : 'Ver horarios del mes' }}
                </p>
            </div>
            <svg class="w-8 h-8 text-green-600 dark:text-green-300" fill="none" stroke="currentColor"
                viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
            </svg>
        </div>
    </a>
</div>