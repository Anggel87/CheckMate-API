<?php

use App\Http\Controllers\Auth\AuthController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Viven fuera de /api porque el navegador navega directo a estas (no son llamadas
// JSON): el popup redirige a gobernanza, y gobernanza redirige de vuelta al callback.
// La URL de callback debe coincidir EXACTO con CHECKMATE_WEB_CALLBACK_URL del .env de
// gobernanza.
Route::get('/auth/popup', [AuthController::class, 'popup'])->name('auth.popup');
Route::get('/auth/callback', [AuthController::class, 'callback'])->name('auth.callback');
