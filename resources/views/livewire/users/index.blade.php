<?php

use Livewire\Volt\Component;
use Livewire\WithPagination;
use App\Models\User;
use App\Models\CatalogoRol;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Livewire\Attributes\On; // Asegúrate de que esta línea esté presente

new class extends Component {
    use WithPagination;

    public bool $isModalOpen = false;
    public bool $generateEmail = false;
    public string $search = '';
    public ?int $userIdBeingEdited = null;
    public bool $showPassword = false;
    public $name = '';
    public $employee_number = '';
    public $email = '';
    public $role_id = '';
    public $password = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }
    
    public function with(): array
    {
        $usersQuery = User::with('role');

        if (!empty($this->search)) {
            $usersQuery->where(function ($query) {
                $searchTerm = '%' . $this->search . '%';
                $query->where('name', 'like', $searchTerm)
                      ->orWhere('email', 'like', $searchTerm)
                      ->orWhere('employee_number', 'like', $searchTerm)
                      // Búsqueda en la relación 'role'
                      ->orWhereHas('role', function ($q) use ($searchTerm) {
                          $q->where('nombre', 'like', $searchTerm);
                      });
            });
        }

        return [
            'users' => $usersQuery->orderBy('name')->paginate(10),
            'roles' => CatalogoRol::all(),
        ];
    }

    protected function rules(): array
    {
        $userIdToIgnore = $this->userIdBeingEdited;

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('users', 'name')->ignore($userIdToIgnore),
            ],
            'employee_number' => [
                'required',
                'string',
                'max:255',
                Rule::unique('users', 'employee_number')->ignore($userIdToIgnore),
            ],
            'email' => [
                Rule::requiredIf(!$this->generateEmail),
                'nullable',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($userIdToIgnore),
            ],
            'role_id' => 'required|exists:catalogo_roles,id',
            // La contraseña es opcional al editar
            'password' => ['nullable', 'string', 'min:8'],
        ];
    }


    public function resetInputFields(): void
    {
        $this->reset(['name', 'employee_number', 'email', 'role_id', 'password', 'generateEmail', 'userIdBeingEdited', 'showPassword']);
        $this->resetValidation();
    }

    public function openModal(): void
    {
        $this->resetInputFields();
        $this->isModalOpen = true;
    }

    public function editUser(User $user): void
    {
        $this->resetInputFields(); // Limpia cualquier estado anterior
        $this->userIdBeingEdited = $user->id;
        $this->name = $user->name;
        $this->employee_number = $user->employee_number;
        $this->email = $user->email;
        $this->role_id = $user->role_id;
        // La contraseña no se carga para editar, solo se actualiza si se escribe una nueva.
        // Si el email del usuario fue autogenerado, marcamos el checkbox.
        $this->generateEmail = (str_starts_with($user->email, 'lab') && str_ends_with($user->email, '@lab.com'));

        $this->isModalOpen = true;
    }

    public function closeModal(): void
    {
        $this->isModalOpen = false;
        $this->resetInputFields();
    }


    public function saveUser(): void
    {
        $validatedData = $this->validate();
        $isEditing = !is_null($this->userIdBeingEdited);

        $userData = [
            'name' => $validatedData['name'],
            'employee_number' => $validatedData['employee_number'],
            'role_id' => $validatedData['role_id'],
        ];

        // Manejo del email
        if ($this->generateEmail) {
            // No hacemos nada con $userData['email'] aquí si se va a autogenerar.
        } else {
            $userData['email'] = $validatedData['email'];
        }
        
        // Manejo de la contraseña: solo se actualiza si se provee una
        if (!empty($validatedData['password'])) {
            $userData['password'] = Hash::make($validatedData['password']);
        }

        if ($isEditing) {
            // --- LÓGICA DE ACTUALIZACIÓN ---
            $user = User::findOrFail($this->userIdBeingEdited);
            $user->update($userData);

            if ($this->generateEmail) {
                // Si se marcó autogenerar al editar, se actualiza el email.
                $user->email = 'lab' . $user->id . '@lab.com';
                $user->save();
            }
            $alertMessage = 'Usuario actualizado correctamente.';

        } else {
            // --- LÓGICA DE CREACIÓN ---
            $user = User::create($userData);

            if ($this->generateEmail) {
                $user->email = 'lab' . $user->id . '@lab.com';
                $user->save();
            }
            $alertMessage = 'Usuario creado correctamente.';
        }
        
        $this->closeModal();

        $this->dispatch('swal:toast', [
            'icon' => 'success',
            'title' => $alertMessage
        ]);
    }

    public function confirmToggleStatus(User $user)
    {
        $this->dispatch('swal:confirm', [
            'title' => '¿Estás seguro?',
            'text' => 'Se cambiará el estado del usuario.',
            'icon' => 'question',
            'confirmButtonText' => 'Sí, cambiar',
            'denyButtonText' => 'No, cancelar',
            'method' => 'toggleStatusConfirmed',
            'params' => ['userId' => $user->id]
        ]);
    }

    #[On('toggleStatusConfirmed')]
    public function toggleStatusConfirmed(int $userId)
    {
        $user = User::find($userId);
        if ($user) {
            $user->status = !$user->status;
            $user->save();

            // Corregido con un solo $
            $this->dispatch('swal:toast', [
                'icon' => 'success',
                'title' => 'El estado del usuario ha sido actualizado.'
            ]);
        }
    }
};
?>

<div>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Usuarios') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-teal-950 overflow-hidden shadow-sm sm:rounded-lg">
                <h2 class="text-2xl font-bold text-gray-800 dark:text-gray-200 mb-4">
                    Administrador de usuarios
                </h2>
                <div class="p-6 text-gray-900 dark:text-gray-100">

                    <div class="flex justify-between items-center mb-4">

                        <div class="w-1/2">
                            <input wire:model.live.debounce.300ms="search" type="text"
                                placeholder=" Buscar por nombre, email, rol..."
                                class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm py-2">
                        </div>

                        <button type="button" wire:click="openModal" wire:loading.attr="disabled" {{-- Deshabilita el
                            botón mientras carga --}} wire:target="openModal" {{-- Se activa solo para esta acción --}}
                            class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 disabled:opacity-50"
                            {{-- Agrega 'disabled:opacity-50' --}}>
                            {{-- Ícono de carga que se muestra con wire:loading --}}
                            <svg wire:loading wire:target="openModal" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white"
                                xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                    stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor"
                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                </path>
                            </svg>

                            {{-- El ícono original, que se oculta mientras carga --}}
                            <svg wire:loading.remove wire:target="openModal" xmlns="http://www.w3.org/2000/svg"
                                viewBox="0 0 20 20" fill="currentColor" class="size-5">
                                <path
                                    d="M10 5a3 3 0 1 1-6 0 3 3 0 0 1 6 0ZM1.615 16.428a1.224 1.224 0 0 1-.569-1.175 6.002 6.002 0 0 1 11.908 0c.058.467-.172.92-.57 1.174A9.953 9.953 0 0 1 7 18a9.953 9.953 0 0 1-5.385-1.572ZM16.25 5.75a.75.75 0 0 0-1.5 0v2h-2a.75.75 0 0 0 0 1.5h2v2a.75.75 0 0 0 1.5 0v-2h2a.75.75 0 0 0 0-1.5h-2v-2Z" />
                            </svg>

                            <span wire:loading.remove wire:target="openModal">&nbsp; Crear Usuario</span>
                            <span wire:loading wire:target="openModal">&nbsp; Abriendo...</span>
                        </button>
                    </div>
                    <br>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            {{-- ... Tu tabla de usuarios se mantiene igual ... --}}
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th scope="col"
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        Nombre</th>
                                    <th scope="col"
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        Correo Electrónico</th>
                                    <th scope="col"
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        Numero Empleado</th>
                                    <th scope="col"
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        Rol</th>
                                    <th scope="col"
                                        class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        Acción</th>
                                    <th scope="col"
                                        class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        Editar</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-teal-950 divide-y divide-gray-200 dark:divide-gray-700">
                                @forelse ($users as $user)
                                <tr>
                                    <td
                                        class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                                        {{ $user->name }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">{{
                                        $user->email }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">{{
                                        $user->employee_number }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">{{
                                        $user->role?->nombre ?? 'Sin rol' }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium">
                                        @if ($user->status == 1) {{-- User is currently ACTIVE --}}
                                        <button wire:click="confirmToggleStatus({{ $user->id }})"
                                            class="inline-flex items-center px-3 py-1 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-500 active:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                                stroke-width="1.5" stroke="currentColor" class="w-4 h-4 mr-1">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                                            </svg>
                                        </button>
                                        @else {{-- User is currently INACTIVE --}}
                                        <button wire:click="confirmToggleStatus({{ $user->id }})"
                                            class="inline-flex items-center px-3 py-1 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-500 active:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                                stroke-width="1.5" stroke="currentColor" class="w-4 h-4 mr-1">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                        </button>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium">
                                        <button wire:click="editUser({{ $user->id }})"
                                            class="inline-flex items-center px-3 py-1 bg-blue-500 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-600 active:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                                stroke-width="1.5" stroke="currentColor" class="w-4 h-4 mr-1">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L6.832 19.82a4.5 4.5 0 01-1.897 1.13l-2.685.8.8-2.685a4.5 4.05 0 011.13-1.897L16.863 4.487zm0 0L19.5 7.125" />
                                            </svg>
                                        </button>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="5"
                                        class="px-6 py-4 whitespace-nowrap text-sm text-center text-gray-500 dark:text-gray-300">
                                        No se encontraron usuarios.</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4">
                        {{ $users->links() }}
                    </div>

                </div>
            </div>
        </div>
    </div>

    @if ($isModalOpen)
    <div class="fixed inset-0 flex items-center justify-center z-50">
        <div class="fixed inset-0 bg-black opacity-50" wire:click="closeModal"></div>
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl p-6 m-4 max-w-lg w-full z-10">

            <form wire:submit.prevent="saveUser">
                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">
                    {{ $userIdBeingEdited ? 'Actualizar Usuario' : 'Crear Nuevo Usuario' }}
                </h3>

                <div class="space-y-4">
                    <div>
                        <label for="name"
                            class="block text-sm font-medium text-gray-700 dark:text-gray-300">Nombre</label>
                        <input type="text" id="name" wire:model.blur="name"
                            class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm">
                        @error('name') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label for="employee_number"
                            class="block text-sm font-medium text-gray-700 dark:text-gray-300">Número de
                            Empleado</label>
                        <input type="text" id="employee_number" wire:model.blur="employee_number"
                            class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm">
                        @error('employee_number') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Correo
                            Electrónico</label>
                        <div class="flex items-center space-x-2 mt-1">
                            <input type="email" id="email" wire:model="email" @disabled($generateEmail)
                                class="block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm disabled:bg-gray-200 dark:disabled:bg-gray-700 disabled:cursor-not-allowed">

                            <div class="flex items-center">
                                <input id="generateEmail" type="checkbox" wire:model.live="generateEmail"
                                    class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                <label for="generateEmail"
                                    class="ml-2 block text-sm text-gray-900 dark:text-gray-300">Autogenerar</label>
                            </div>
                        </div>
                        @error('email') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label for="role_id"
                            class="block text-sm font-medium text-gray-700 dark:text-gray-300">Rol</label>
                        <select id="role_id" wire:model="role_id"
                            class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm">
                            <option value="">Seleccione un rol</option>
                            @foreach ($roles as $role)
                            <option value="{{ $role->id }}">{{ $role->nombre }}</option>
                            @endforeach
                        </select>
                        @error('role_id') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label for="password"
                            class="block text-sm font-medium text-gray-700 dark:text-gray-300">Contraseña</label>

                        {{-- 1. Contenedor con posición relativa --}}
                        <div class="relative mt-1">

                            {{-- 2. El input ahora tiene un padding a la derecha (pr-10) para hacer espacio al ícono
                            --}}
                            <input id="password" wire:model="password" {{-- 3. El tipo de input es dinámico --}}
                                type="{{ $showPassword ? 'text' : 'password' }}"
                                class="block w-full pr-10 border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm"
                                placeholder="{{ $userIdBeingEdited ? '(Dejar en blanco para no cambiar)' : '' }}">

                            {{-- 4. El botón con el ícono, posicionado de forma absoluta --}}
                            <button type="button" {{-- 5. Al hacer clic, se alterna el valor de $showPassword --}}
                                wire:click="$toggle('showPassword')"
                                class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                                {{-- 6. Se muestra un ícono u otro dependiendo del estado --}}
                                @if ($showPassword)
                                {{-- Ícono de "Ojo Abierto" (ocultar contraseña) --}}
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                    stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                </svg>
                                @else
                                {{-- Ícono de "Ojo Cerrado" (mostrar contraseña) --}}
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                    stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.243 4.243L6.228 6.228" />
                                </svg>
                                @endif
                            </button>
                        </div>
                        @error('password') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div class="flex justify-end space-x-4 mt-6">
                    <button type="button" wire:click="closeModal"
                        class="px-4 py-2 bg-gray-300 text-gray-800 rounded-md hover:bg-gray-400">
                        Cancelar
                    </button>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                        {{ $userIdBeingEdited ? 'Actualizar' : 'Guardar' }}
                    </button>
                </div>
            </form>

        </div>
    </div>
    @endif
</div>