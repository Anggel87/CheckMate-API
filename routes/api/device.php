<?php

use App\Http\Controllers\Device\NfcController;
use Illuminate\Support\Facades\Route;

Route::prefix('device')->group(function () {
    Route::post('/nfc', [NfcController::class, 'handle']);
});
