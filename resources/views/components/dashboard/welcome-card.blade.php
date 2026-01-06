@props(['user', 'planningPeriod'])

<div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg mb-6">
    <div class="p-6 text-gray-900 dark:text-gray-100">
        <div class="flex justify-between items-center">
            <div>
                <h3 class="text-lg font-semibold mb-2">¡Bienvenido, {{ $user->name }}!</h3>
                <p class="text-gray-600 dark:text-gray-400">
                    <span class="font-medium">Área:</span> {{ $user->work_area }} |
                    <span class="font-medium">Rol:</span>
                    @if($user->role === 'admin')
                        <span class="text-red-600 dark:text-red-400">Administrador</span>
                    @elseif($user->role === 'manager')
                        <span class="text-blue-600 dark:text-blue-400">Manager</span>
                    @else
                        <span class="text-green-600 dark:text-green-400">Usuario</span>
                    @endif
                </p>
            </div>
            <div class="text-right">
                <span
                    class="inline-flex items-center px-3 py-1 rounded-full text-sm {{ $planningPeriod['isActive'] ? 'bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100' : 'bg-yellow-100 text-yellow-800 dark:bg-yellow-800 dark:text-yellow-100' }}">
                    {{ $planningPeriod['isActive'] ? '✅ Período de planificación activo' : '⏰ Fuera de período' }}
                </span>
            </div>
        </div>
    </div>
</div>