<?php

use App\Http\Controllers\Alumno\ClaimController;
use App\Http\Controllers\Alumno\JustificationController;
use App\Http\Controllers\Alumno\NotificationController;
use App\Http\Controllers\Alumno\ProfileController;
use App\Http\Controllers\Alumno\ScheduleController;
use App\Http\Controllers\Alumno\SubjectController;
use App\Http\Controllers\Alumno\TeacherController;
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
        Route::put('/claims/{claim}', [ClaimController::class, 'update'])->name('claims.update');
        Route::delete('/claims/{claim}', [ClaimController::class, 'destroy'])->name('claims.destroy');

        Route::get('/justifications', [JustificationController::class, 'index'])->name('justifications.index');
        Route::get('/justifications/{justification}', [JustificationController::class, 'show'])->name('justifications.show');
        Route::put('/justifications/{justification}', [JustificationController::class, 'update'])->name('justifications.update');
        Route::delete('/justifications/{justification}', [JustificationController::class, 'destroy'])->name('justifications.destroy');

        Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
        Route::get('/notifications/{notification}', [NotificationController::class, 'show'])->name('notifications.show');
        Route::patch('/notifications/{notification}/read', [NotificationController::class, 'markRead'])->name('notifications.read');

        Route::get('/teachers', [TeacherController::class, 'index'])->name('teachers.index');
        Route::get('/teachers/{teacher}', [TeacherController::class, 'show'])->name('teachers.show');

        Route::get('/schedule', [ScheduleController::class, 'index'])->name('schedule.index');

        Route::get('/subjects', [SubjectController::class, 'index'])->name('subjects.index');
        Route::get('/attendance', [SubjectController::class, 'allAttendance'])->name('attendance.index');
        Route::get('/subjects/{subject}', [SubjectController::class, 'show'])->name('subjects.show');
        Route::get('/subjects/{subject}/attendance', [SubjectController::class, 'attendance'])->name('subjects.attendance');
        Route::post('/subjects/{subject}/attendance/{attendance}/justify', [JustificationController::class, 'store'])->name('subjects.attendance.justify');
        Route::get('/incidents/active', [\App\Http\Controllers\Profesor\IncidentController::class, 'active'])->name('incidents.active');
    });
