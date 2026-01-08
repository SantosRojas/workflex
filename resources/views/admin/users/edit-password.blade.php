<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                🔐 Cambiar Contraseña - {{ $user->name }} {{ $user->last_name }}
            </h2>
            <a href="{{ route('admin.users.index') }}"
               class="inline-flex items-center px-4 py-2 bg-gray-600 dark:bg-gray-700 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-500 dark:hover:bg-gray-600">
                ← Volver
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            
            {{-- Información del usuario --}}
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6">
                    <div class="grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <p class="text-gray-600 dark:text-gray-400">Email</p>
                            <p class="text-gray-900 dark:text-gray-100 font-semibold">{{ $user->email }}</p>
                        </div>
                        <div>
                            <p class="text-gray-600 dark:text-gray-400">Área de Trabajo</p>
                            <p class="text-gray-900 dark:text-gray-100 font-semibold">{{ $user->work_area }}</p>
                        </div>
                        <div>
                            <p class="text-gray-600 dark:text-gray-400">Rol</p>
                            <p class="text-gray-900 dark:text-gray-100 font-semibold">
                                @switch($user->role)
                                    @case('admin')
                                        🔐 Administrador
                                        @break
                                    @case('manager')
                                        👔 Manager
                                        @break
                                    @case('user')
                                        👤 Usuario
                                        @break
                                @endswitch
                            </p>
                        </div>
                        <div>
                            <p class="text-gray-600 dark:text-gray-400">Miembro desde</p>
                            <p class="text-gray-900 dark:text-gray-100 font-semibold">{{ $user->created_at->format('d/m/Y') }}</p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Formulario de cambio de contraseña --}}
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-6">
                        Ingresa la nueva contraseña
                    </h3>

                    @if($errors->any())
                        <div class="mb-4 bg-red-100 dark:bg-red-900 border border-red-400 dark:border-red-600 text-red-700 dark:text-red-300 px-4 py-3 rounded">
                            @foreach($errors->all() as $error)
                                <p>{{ $error }}</p>
                            @endforeach
                        </div>
                    @endif

                    <form action="{{ route('admin.users.update-password', $user) }}" method="POST" class="space-y-6">
                        @csrf
                        @method('PUT')

                        <div>
                            <x-input-label for="password" value="Nueva Contraseña" />
                            <x-text-input 
                                id="password" 
                                class="block mt-1 w-full" 
                                type="password" 
                                name="password" 
                                required
                                minlength="8"
                                autocomplete="new-password" />
                            <x-input-error :messages="$errors->get('password')" class="mt-2" />
                            <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                                Mínimo 8 caracteres
                            </p>
                        </div>

                        <div>
                            <x-input-label for="password_confirmation" value="Confirmar Contraseña" />
                            <x-text-input 
                                id="password_confirmation" 
                                class="block mt-1 w-full" 
                                type="password" 
                                name="password_confirmation" 
                                required
                                minlength="8"
                                autocomplete="new-password" />
                            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
                        </div>

                        <div class="bg-yellow-50 dark:bg-yellow-900 p-4 rounded-lg">
                            <p class="text-sm text-yellow-800 dark:text-yellow-200">
                                <span class="font-semibold">⚠️ Importante:</span> Al guardar, la sesión del usuario será invalidada y deberá volver a iniciar sesión con la nueva contraseña.
                            </p>
                        </div>

                        <div class="flex justify-end gap-3">
                            <a href="{{ route('admin.users.index') }}"
                               class="inline-flex items-center px-4 py-2 bg-gray-600 dark:bg-gray-700 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-500 dark:hover:bg-gray-600">
                                Cancelar
                            </a>
                            <x-primary-button>
                                💾 Guardar Nueva Contraseña
                            </x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
