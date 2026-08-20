<?php

use App\Http\Controllers\Alumno\ClaimController;
use App\Http\Controllers\Alumno\JustificationController;
use App\Http\Controllers\Alumno\ProfileController;
use App\Http\Controllers\Alumno\SubjectController;
use Illuminate\Support\Facades\Route;

Route::prefix('alumno')
    ->name('alumno.')
    ->middleware(['governance.auth', 'role:alumno'])
    ->group(function () {
        Route::get('/profile', [ProfileController::class, 'show'])->name('profile');
        Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');

        Route::get('/claims', [ClaimController::class, 'index'])->name('claims.index');
        Route::get('/claims/{claim}', [ClaimController::class, 'show'])->name('claims.show');
        Route::post('/claims', [ClaimController::class, 'store'])->name('claims.store');

        Route::get('/justifications', [JustificationController::class, 'index'])->name('justifications.index');
        Route::get('/justifications/{justification}', [JustificationController::class, 'show'])->name('justifications.show');

        Route::get('/subjects', [SubjectController::class, 'index'])->name('subjects.index');
        Route::get('/attendance', [SubjectController::class, 'allAttendance'])->name('attendance.index');
        Route::get('/subjects/{subject}', [SubjectController::class, 'show'])->name('subjects.show');
        Route::get('/subjects/{subject}/attendance', [SubjectController::class, 'attendance'])->name('subjects.attendance');
        Route::post('/subjects/{subject}/attendance/{attendance}/justify', [JustificationController::class, 'store'])->name('subjects.attendance.justify');
        Route::get('/incidents/active', [\App\Http\Controllers\Profesor\IncidentController::class, 'active'])->name('incidents.active');
    });
