<?php

use App\Http\Controllers\Test\GovernanceTestController;
use Illuminate\Support\Facades\Route;

if (app()->environment('local')) {
    Route::prefix('test/governance')->name('test.governance.')->group(function () {
        Route::post('/create-user', [GovernanceTestController::class, 'createUser'])->name('create-user');
        Route::post('/login', [GovernanceTestController::class, 'login'])->name('login');
        Route::get('/popup', [GovernanceTestController::class, 'popup'])->name('popup');
        Route::get('/callback', [GovernanceTestController::class, 'callback'])->name('callback');
    });
}
