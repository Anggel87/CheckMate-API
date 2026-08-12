<?php

use App\Http\Controllers\Tutor\ClaimController;
use App\Http\Controllers\Tutor\JustificationController;
use Illuminate\Support\Facades\Route;

Route::prefix('tutor')
    ->name('tutor.')
    ->middleware(['governance.auth', 'role:tutor_academico'])
    ->group(function () {
        Route::get('/claims', [ClaimController::class, 'index'])->name('claims.index');
        Route::get('/claims/{claim}', [ClaimController::class, 'show'])->name('claims.show');
        Route::patch('/claims/{claim}/action', [ClaimController::class, 'action'])->name('claims.action');

        Route::post('/students/{student}/justifications', [JustificationController::class, 'store'])->name('students.justifications.store');
        Route::patch('/students/{student}/justifications/{justification}', [JustificationController::class, 'update'])->name('students.justifications.update');
    });
