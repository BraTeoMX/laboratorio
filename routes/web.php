<?php

use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\TelasExportController;
use App\Livewire\Reportes\ReporteInspeccionRango;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

// --- Entry Point ---
// Redirige inteligentemente al dashboard correspondiente si el usuario ya está autenticado.
Route::get('/', function () {
    return Auth::check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
})->name('home');

// --- Authenticated Routes ---
Route::middleware(['auth', 'verified'])->group(function () {

    // --- Dashboards ---
    // El middleware 'role.redirect' se encarga de la distribución según el rol.
    Route::view('dashboard', 'dashboard')
        ->middleware('role.redirect')
        ->name('dashboard');

    Route::view('dashboard2', 'dashboard2')->name('dashboard.alternate');

    // Vistas específicas por Rol (Renombradas para consistencia)
    Route::view('vista-auditor', 'vistaAuditor')->name('dashboard.auditor'); // Antes: vistaAuditor
    Route::view('vista-gestor', 'vistaGestor')->name('dashboard.manager');   // Antes: vistaGestor

    // --- User Management ---
    Volt::route('users', 'users.index')->name('users.index');

    // --- Settings Module ---
    Route::prefix('settings')->name('settings.')->group(function () {
        Route::redirect('/', '/settings/profile');
        Volt::route('profile', 'settings.profile')->name('profile');
        Volt::route('password', 'settings.password')->name('password');
        Volt::route('appearance', 'settings.appearance')->name('appearance');
    });

    // --- Quality Control Module (Calidad) ---
    Route::prefix('calidad')->name('calidad.')->group(function () {
        // Operaciones principales
        Volt::route('inspeccion-tela', 'calidad.inspeccion-tela')->name('inspeccion');
        Volt::route('auditoria-materia-prima', 'calidad.auditoria-materia-prima')->name('auditoria');
    });

    // --- Reports Module (Reportes) ---
    Route::prefix('reportes')->name('reportes.')->group(function () {
        // Reportes específicos
        Route::get('inspeccion', ReporteInspeccionRango::class)->name('inspeccion');
    });
    // --- Fabrics Inventory (Telas) ---
    Route::prefix('telas')->name('telas.')->group(function () {
        Route::view('/', 'telas.index')->name('index');

        // Exportaciones
        Route::controller(TelasExportController::class)->prefix('export')->name('export.')->group(function () {
            Route::get('pdf', 'exportPDF')->name('pdf');
            Route::get('excel', 'exportExcel')->name('excel');
        });
    });

    // --- Reports (General) ---
    // Si hay más reportes generales, agrégalos aquí. 
    // Por ahora, 'inspeccion' se movió al módulo de Calidad por cohesión.
    // Route::prefix('reportes')->name('reportes.')->group(function () { ... });

});

require __DIR__ . '/auth.php';
