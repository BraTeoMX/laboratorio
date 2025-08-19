<?php

use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;
use Illuminate\Support\Facades\Auth;

// Ruta de bienvenida para visitantes (no autenticados)
Route::get('/', function () {
    return view('welcome');
})->name('home');

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
    
});

require __DIR__.'/auth.php';