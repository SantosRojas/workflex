<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight flex items-center gap-2">
                <x-icons.users class="w-6 h-6" /> Gestión de Usuarios
            </h2>
            <a href="{{ route('admin.settings') }}"
               class="inline-flex items-center px-4 py-2 bg-gray-600 dark:bg-gray-700 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-500 dark:hover:bg-gray-600">
                <x-icons.settings class="w-4 h-4 mr-1" /> Configuración del Sistema
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            
            {{-- Mensajes de éxito/error --}}
            @if(session('success'))
                <div class="mb-4 bg-green-100 dark:bg-green-900 border border-green-400 dark:border-green-600 text-green-700 dark:text-green-300 px-4 py-3 rounded flex items-center">
                    <x-icons.check class="w-5 h-5 mr-2" /> {{ session('success') }}
                </div>
            @endif
            
            @if($errors->any())
                <div class="mb-4 bg-red-100 dark:bg-red-900 border border-red-400 dark:border-red-600 text-red-700 dark:text-red-300 px-4 py-3 rounded">
                    @foreach($errors->all() as $error)
                        <p class="flex items-center"><x-icons.warning class="w-5 h-5 mr-2" /> {{ $error }}</p>
                    @endforeach
                </div>
            @endif

            {{-- Panel de búsqueda y filtrado --}}
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6">
                    <form method="GET" action="{{ route('admin.users.index') }}" class="space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            {{-- Búsqueda --}}
                            <div>
                                <label for="search" class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">
                                    Buscar usuario
                                </label>
                                <input type="text" 
                                       name="search" 
                                       id="search" 
                                       value="{{ $search }}"
                                       placeholder="Nombre, apellido o email..."
                                       class="block w-full h-10 rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 focus:border-indigo-500 focus:ring-indigo-500">
                            </div>

                            {{-- Filtro por rol --}}
                            <div>
                                <label for="role" class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">
                                    Filtrar por rol
                                </label>
                                <select name="role" id="role"
                                        class="block w-full h-10 rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="">Todos los roles</option>
                                    @foreach($roles as $role)
                                        <option value="{{ $role }}" {{ $roleFilter === $role ? 'selected' : '' }}>
                                            @switch($role)
                                                @case('admin')
                                                    Administrador
                                                    @break
                                                @case('manager')
                                                    Manager
                                                    @break
                                                @case('user')
                                                    Usuario
                                                    @break
                                            @endswitch
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- Botón buscar --}}
                            <div class="flex items-end">
                                <x-primary-button class="w-full justify-center h-10">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                                    Buscar
                                </x-primary-button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            {{-- Tabla de usuarios --}}
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4">
                        Lista de Usuarios
                    </h3>

                    @if($users->count() > 0)
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-700">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                            Nombre
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                            Email
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                            Área
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                            Rol
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                            Acciones
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                    @foreach($users as $user)
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-100">
                                                {{ $user->name }} {{ $user->last_name }}
                                                @if($user->id === Auth::id())
                                                    <span class="ml-2 text-xs bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200 px-2 py-1 rounded">
                                                        (Tú)
                                                    </span>
                                                @endif
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400">
                                                {{ $user->email }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400">
                                                {{ $user->work_area }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="px-3 py-1 rounded-full text-xs font-semibold flex items-center w-fit
                                                    @switch($user->role)
                                                        @case('admin')
                                                            bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200
                                                            @break
                                                        @case('manager')
                                                            bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200
                                                            @break
                                                        @case('user')
                                                            bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200
                                                            @break
                                                    @endswitch">
                                                    @switch($user->role)
                                                        @case('admin')
                                                            <x-icons.key class="w-3 h-3 mr-1" /> Admin
                                                            @break
                                                        @case('manager')
                                                            <x-icons.users class="w-3 h-3 mr-1" /> Manager
                                                            @break
                                                        @case('user')
                                                            <x-icons.user class="w-3 h-3 mr-1" /> Usuario
                                                            @break
                                                    @endswitch
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                                                {{-- Cambiar contraseña --}}
                                                <a href="{{ route('admin.users.edit-password', $user) }}"
                                                   class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300"
                                                   title="Cambiar contraseña">
                                                    <x-icons.key class="w-5 h-5 inline-block" />
                                                </a>

                                                {{-- Cambiar rol --}}
                                                <a href="{{ route('admin.users.edit-role', $user) }}"
                                                   class="text-purple-600 hover:text-purple-900 dark:text-purple-400 dark:hover:text-purple-300"
                                                   title="Cambiar rol">
                                                    <x-icons.users class="w-5 h-5 inline-block" />
                                                </a>

                                                {{-- Cambiar área --}}
                                                <a href="{{ route('admin.users.edit-area', $user) }}"
                                                   class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300"
                                                   title="Cambiar área">
                                                    <x-icons.office class="w-5 h-5 inline-block" />
                                                </a>

                                                {{-- Eliminar (solo si no es el usuario actual) --}}
                                                @if($user->id !== Auth::id())
                                                    <form action="{{ route('admin.users.destroy', $user) }}" method="POST" style="display:inline;">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit"
                                                                class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300"
                                                                title="Eliminar usuario"
                                                                onclick="return confirm('¿Eliminar este usuario? Esta acción no se puede deshacer.')">
                                                            <x-icons.delete class="w-5 h-5 inline-block" />
                                                        </button>
                                                    </form>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        {{-- Paginación --}}
                        <div class="mt-6">
                            {{ $users->links() }}
                        </div>
                    @else
                        <div class="text-center py-8">
                            <p class="text-gray-600 dark:text-gray-400">No se encontraron usuarios.</p>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Leyenda de acciones --}}
            <div class="mt-6 bg-blue-50 dark:bg-blue-900 p-4 rounded-lg">
                <h3 class="font-semibold text-blue-800 dark:text-blue-200 mb-2 flex items-center gap-2">
                    <x-icons.info class="w-5 h-5" /> Acciones disponibles:
                </h3>
                <ul class="text-sm text-blue-700 dark:text-blue-300 space-y-2">
                    <li class="flex items-center gap-2">
                        <x-icons.key class="w-4 h-4 text-indigo-600" /> Cambiar contraseña
                    </li>
                    <li class="flex items-center gap-2">
                        <x-icons.users class="w-4 h-4 text-purple-600" /> Cambiar rol
                    </li>
                    <li class="flex items-center gap-2">
                        <x-icons.office class="w-4 h-4 text-blue-600" /> Cambiar área de trabajo
                    </li>
                    <li class="flex items-center gap-2">
                        <x-icons.delete class="w-4 h-4 text-red-600" /> Eliminar usuario
                    </li>
                </ul>
            </div>
        </div>
    </div>
</x-app-layout>
