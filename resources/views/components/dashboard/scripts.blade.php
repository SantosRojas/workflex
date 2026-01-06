@props(['homeOfficeByDate', 'flexibleByArea'])

<script>
    // Datos de Home Office
    const assignmentsByDate = @json($homeOfficeByDate);

    // Datos de Horarios Flexibles por área
    const flexibleByArea = @json($flexibleByArea);

    // Funciones para modal de Home Office
    function openDayModal(dateKey, formattedDate) {
        const modal = document.getElementById('dayModal');
        const title = document.getElementById('modalTitle');
        const content = document.getElementById('modalContent');
        const link = document.getElementById('modalLink');

        title.innerHTML = '🏠 ' + formattedDate;
        link.href = '{{ route("home-office.index") }}';

        const assignments = assignmentsByDate[dateKey] || [];

        if (assignments.length > 0) {
            content.innerHTML = assignments.map(a => `
                <div class="flex items-center p-3 bg-blue-50 dark:bg-blue-900 rounded-lg">
                    <div class="w-2 h-2 bg-blue-500 rounded-full mr-3"></div>
                    <div>
                        <p class="font-medium text-gray-800 dark:text-gray-200">${a.name}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">${a.area}</p>
                    </div>
                </div>
            `).join('');
        } else {
            content.innerHTML = '<p class="text-gray-500 dark:text-gray-400">No hay personas en home office este día.</p>';
        }

        modal.classList.remove('hidden');
    }

    function closeDayModal() {
        document.getElementById('dayModal').classList.add('hidden');
    }

    // Funciones para modal de Horarios Flexibles
    function openFlexibleModal(area) {
        const modal = document.getElementById('flexibleModal');
        const title = document.getElementById('flexibleModalTitle');
        const content = document.getElementById('flexibleModalContent');

        title.innerHTML = '⏰ ' + area;

        const assignments = flexibleByArea[area] || [];

        if (assignments.length > 0) {
            content.innerHTML = assignments.map(a => {
                let colorClass = 'bg-purple-100 text-purple-700 dark:bg-purple-800 dark:text-purple-200';
                if (a.time === '08:00') colorClass = 'bg-green-100 text-green-700 dark:bg-green-800 dark:text-green-200';
                else if (a.time === '08:30') colorClass = 'bg-yellow-100 text-yellow-700 dark:bg-yellow-800 dark:text-yellow-200';
                else if (a.time === '09:00') colorClass = 'bg-blue-100 text-blue-700 dark:bg-blue-800 dark:text-blue-200';

                return `
                    <div class="flex items-center justify-between p-3 bg-green-50 dark:bg-green-900/30 rounded-lg">
                        <div class="flex items-center">
                            <div class="w-2 h-2 bg-green-500 rounded-full mr-3"></div>
                            <p class="font-medium text-gray-800 dark:text-gray-200">${a.name}</p>
                        </div>
                        <span class="px-2 py-1 rounded text-xs font-semibold ${colorClass}">${a.time}</span>
                    </div>
                `;
            }).join('');
        } else {
            content.innerHTML = '<p class="text-gray-500 dark:text-gray-400">No hay personas con horario flexible en esta área.</p>';
        }

        modal.classList.remove('hidden');
    }

    function closeFlexibleModal() {
        document.getElementById('flexibleModal').classList.add('hidden');
    }

    // Cerrar modales al hacer clic fuera
    document.getElementById('dayModal').addEventListener('click', function (e) {
        if (e.target === this) closeDayModal();
    });

    document.getElementById('flexibleModal').addEventListener('click', function (e) {
        if (e.target === this) closeFlexibleModal();
    });
</script>