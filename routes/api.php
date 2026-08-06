<?php

use App\Http\Controllers\MeController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::prefix('v1')->group(function () {
    require __DIR__.'/api/auth.php';

    Route::get('/me', [MeController::class, 'show'])
        ->middleware('governance.auth')
        ->name('me');

    require __DIR__.'/api/alumno.php';
    require __DIR__.'/api/profesor.php';
    require __DIR__.'/api/tutor.php';
    require __DIR__.'/api/device.php';
});
