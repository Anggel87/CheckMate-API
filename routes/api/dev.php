<?php

use App\Http\Controllers\Dev\DevToolsController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rutas de desarrollador
|--------------------------------------------------------------------------
|
| El endpoint echo está disponible en cualquier entorno para inspeccionar peticiones
| de la API. Las demás herramientas, que modifican sesiones y horarios, solo se
| cargan en local/testing. Ninguna de estas rutas usa autenticación.
|
*/

Route::prefix('dev')->group(function () {
    Route::any('echo', [DevToolsController::class, 'echoRequest']);

    if (app()->environment(['local', 'testing'])) {
        Route::post('schedules/{schedule}/activate-now', [DevToolsController::class, 'activateScheduleNow']);
        Route::post('schedules/{schedule}/reset-session', [DevToolsController::class, 'resetSession']);
        Route::get('schedules/{schedule}/status', [DevToolsController::class, 'scheduleStatus']);
        Route::post('class-sessions/{class_session}/close-now', [DevToolsController::class, 'closeSessionNow']);
    }
});
