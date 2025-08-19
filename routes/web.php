<?php

use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;
use Illuminate\Support\Facades\Auth;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', 'settings/profile');
    Route::view('dashboard2', 'dashboard2')->name('dashboard2');

    Volt::route('settings/profile', 'settings.profile')->name('settings.profile');
    Volt::route('settings/password', 'settings.password')->name('settings.password');
    Volt::route('settings/appearance', 'settings.appearance')->name('settings.appearance');
    Volt::route('users', 'users.index')->name('users.index');
});

require __DIR__.'/auth.php';
