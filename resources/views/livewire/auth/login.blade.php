<?php

// Importaciones necesarias para el componente
use App\Models\User;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Volt\Component;

// Definición del componente Volt con el layout de autenticación
new #[Layout('components.layouts.auth')] class extends Component {
    // Propiedad para almacenar la credencial (email o número de empleado)
    #[Validate('required|string')]
    public string $credential = '';

    // Propiedad para la contraseña
    #[Validate('required|string')]
    public string $password = '';

    // Propiedad para la opción "Recordarme"
    public bool $remember = false;

    /**
     * Maneja la solicitud de autenticación.
     */
    public function login(): void
    {
        // Valida los datos del formulario
        $this->validate();

        // Asegura que no se excedan los intentos de inicio de sesión
        $this->ensureIsNotRateLimited();

        // Determina si la credencial es un email o un número de empleado
        $fieldType = filter_var($this->credential, FILTER_VALIDATE_EMAIL) ? 'email' : 'employee_number';

        // Busca al usuario por la credencial para verificar su estado antes de autenticar
        $user = User::where($fieldType, $this->credential)->first();

        // Si el usuario existe pero está inactivo (status = 0)
        if ($user && $user->status == 0) {
            // Registra el intento fallido en el RateLimiter
            RateLimiter::hit($this->throttleKey());

            // Despacha un evento para ser capturado por SweetAlert en el frontend
            $this->dispatch('swal:toast', [
                'icon' => 'info',
                'title' => 'Su usuario está dado de baja. Contacte al administrador.',
                'timer' => 5000
            ]);

            return; // Detiene la ejecución del método
        }

        // Intenta autenticar al usuario con la credencial y contraseña
        if (!Auth::attempt([$fieldType => $this->credential, 'password' => $this->password], $this->remember)) {
            // Si la autenticación falla, registra el intento
            RateLimiter::hit($this->throttleKey());

            // Lanza una excepción de validación con el mensaje de error estándar
            throw ValidationException::withMessages([
                'credential' => __('auth.failed'),
            ]);
        }

        // Si la autenticación es exitosa, limpia los intentos del RateLimiter
        RateLimiter::clear($this->throttleKey());

        // Regenera la sesión para prevenir ataques de fijación de sesión
        Session::regenerate();

        // Obtiene el usuario autenticado para la redirección por rol
        $authenticatedUser = Auth::user();

        // Determina la ruta de destino según el rol del usuario
        $routeName = match ((int) $authenticatedUser->role_id) {
            1, 2, 4    => 'dashboard',
            3           => 'vistaGestor',
            5           => 'vistaAuditor',
            default     => 'dashboard', // Ruta por defecto como respaldo
        };

        // Redirige al usuario a la vista correspondiente
        $this->redirect(route($routeName), navigate: true);
    }

    /**
     * Asegura que la solicitud no esté limitada por intentos fallidos.
     */
    protected function ensureIsNotRateLimited(): void
    {
        if (!RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout(request()));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'credential' => __('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Obtiene la clave para el limitador de intentos (Rate Limiter).
     */
    protected function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->credential) . '|' . request()->ip());
    }
}; ?>

<div class="flex flex-col gap-6">
    <x-auth-header :title="__('Accede con tu cuenta')"
        :description="__('Ingresa tu número de empleado o correo asociado')" />

    <x-auth-session-status class="text-center" :status="session('status')" />

    <form wire:submit="login" class="flex flex-col gap-6">

        <flux:input wire:model="credential" :label="__('Número de empleado o correo electrónico')" type="text" required
            autofocus autocomplete="username" placeholder="empleado@empresa.com o 18080" />

        <div class="relative">
            <flux:input wire:model="password" :label="__('Contraseña')" type="password" required
                autocomplete="current-password" :placeholder="__('Contraseña')" viewable />
        </div>

        <div class="flex items-center justify-end">
            <flux:button variant="primary" type="submit" class="w-full">
                {{ __('Iniciar sesión') }}
            </flux:button>
        </div>

    </form>
</div>