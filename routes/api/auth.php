<?php

use App\Http\Controllers\Auth\AuthController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->name('auth.')->group(function () {
    Route::post('/users', [AuthController::class, 'createUser'])
        ->middleware(['governance.auth', 'role:administrador'])
        ->name('users.store');
    Route::post('/login', [AuthController::class, 'login'])->name('login');
});
