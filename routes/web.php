<?php

use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;
use Illuminate\Support\Facades\Auth;

// Ruta raíz inteligente que redirige según el estado de autenticación.
Route::get('/', function () {
    // Si el usuario ya está autenticado...
    if (Auth::check()) {
        // ...lo enviamos al 'dashboard' principal. Tu middleware 'role.redirect'
        // se encargará de llevarlo al dashboard correcto (dashboard o dashboard2).
        return redirect()->route('dashboard');
    }

    // Si el usuario NO está autenticado (es un visitante)...
    // ...lo enviamos a la página de inicio de sesión.
    return redirect()->route('login');
})->name('home'); // Mantenemos el nombre 'home' por si se usa en otro lugar.


// Grupo principal para TODAS las rutas que requieren autenticación y email verificado
Route::middleware(['auth', 'verified'])->group(function () {

    // 1. RUTA PRINCIPAL DE DASHBOARD
    //    - Es a donde los usuarios son redirigidos después de iniciar sesión.
    //    - Aplicamos el middleware 'role.redirect' para que actúe como un "distribuidor".
    Route::view('dashboard', 'dashboard')
        ->middleware('role.redirect') // <-- ¡Este es el paso clave que faltaba!
        ->name('dashboard');

    // 2. EL DASHBOARD SECUNDARIO
    //    Lo ponemos dentro del mismo grupo porque también requiere autenticación.
    Route::view('dashboard2', 'dashboard2')->name('dashboard2');

    // 3. RUTAS DE CONFIGURACIÓN Y OTRAS
    //    Agrupadas por prefijo para mantener el orden.
    Route::prefix('settings')->name('settings.')->group(function () {
        Route::redirect('/', '/settings/profile');
        Volt::route('profile', 'settings.profile')->name('profile');
        Volt::route('password', 'settings.password')->name('password');
        Volt::route('appearance', 'settings.appearance')->name('appearance');
    });

    Volt::route('users', 'users.index')->name('users.index');
    Volt::route('calidad/inspeccion-tela', 'calidad.reporte-inspeccion')->name('calidad.inspeccion');
    Volt::route('calidad/auditoria-materia-prima', 'calidad.auditoria-materia-prima')->name('calidad.auditoria');

    // Vista específica para auditores (role_id = 5)
    Route::view('vista-auditor', 'vistaAuditor')->name('vistaAuditor');

    // Vista específica para gestores (role_id = 3)
    Route::view('vista-gestor', 'vistaGestor')->name('vistaGestor');

});

require __DIR__.'/auth.php';