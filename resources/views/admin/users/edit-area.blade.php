<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight flex items-center gap-2">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                </svg>
                Cambiar Área - {{ $user->name }} {{ $user->last_name }}
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
                        <div class="col-span-2">
                            <p class="text-gray-600 dark:text-gray-400">Área Actual</p>
                            <p class="text-gray-900 dark:text-gray-100 font-semibold text-lg">{{ $user->work_area }}</p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Formulario de cambio de área --}}
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-6">
                        Actualizar área de trabajo
                    </h3>

                    @if($errors->any())
                        <div class="mb-4 bg-red-100 dark:bg-red-900 border border-red-400 dark:border-red-600 text-red-700 dark:text-red-300 px-4 py-3 rounded">
                            @foreach($errors->all() as $error)
                                <p>{{ $error }}</p>
                            @endforeach
                        </div>
                    @endif

                    <form action="{{ route('admin.users.update-area', $user) }}" method="POST" class="space-y-6">
                        @csrf
                        @method('PUT')

                        <div>
                            <label for="work_area" class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-2">
                                Nueva Área de Trabajo
                            </label>
                            <input type="text" 
                                   id="work_area"
                                   name="work_area" 
                                   value="{{ old('work_area', $user->work_area) }}"
                                   placeholder="Ej: Recursos Humanos, Tecnología, Ventas..."
                                   required
                                   class="block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 focus:border-indigo-500 focus:ring-indigo-500">
                            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                                Ingresa el nombre del área o departamento al que pertenece el usuario.
                            </p>
                        </div>

                        <div class="bg-blue-50 dark:bg-blue-900 p-4 rounded-lg">
                            <p class="text-sm text-blue-800 dark:text-blue-200 flex items-start gap-2">
                                <svg class="w-5 h-5 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <span><span class="font-semibold">Nota:</span> Si el usuario es un Manager, cambiar el área afectará qué empleados puede gestionar. Los managers solo pueden gestionar usuarios de su misma área.</span>
                            </p>
                        </div>

                        <div class="flex justify-end gap-3">
                            <a href="{{ route('admin.users.index') }}"
                               class="inline-flex items-center px-4 py-2 bg-gray-600 dark:bg-gray-700 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-500 dark:hover:bg-gray-600">
                                Cancelar
                            </a>
                            <x-primary-button>
                                💾 Guardar Cambio de Área
                            </x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
