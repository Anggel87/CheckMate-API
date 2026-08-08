<?php

use App\Http\Controllers\Dev\DevToolsController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rutas de desarrollador
|--------------------------------------------------------------------------
|
| Solo se cargan cuando APP_ENV=local (ver routes/api.php) — sirven para probar y
| demostrar el flujo de asistencia sin esperar a que el reloj real coincida con un
| horario. Sin middleware de autenticación, mismo criterio que POST /device/nfc: son
| una herramienta de prototipo, no un endpoint de producción.
|
*/

Route::prefix('dev')->group(function () {
    Route::post('schedules/{schedule}/activate-now', [DevToolsController::class, 'activateScheduleNow']);
    Route::post('schedules/{schedule}/reset-session', [DevToolsController::class, 'resetSession']);
    Route::get('schedules/{schedule}/status', [DevToolsController::class, 'scheduleStatus']);
    Route::post('class-sessions/{class_session}/close-now', [DevToolsController::class, 'closeSessionNow']);
});
