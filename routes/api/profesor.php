<?php

use App\Http\Controllers\Profesor\ClaimController;
use App\Http\Controllers\Profesor\GroupController;
use App\Http\Controllers\Profesor\IncidentController;
use App\Http\Controllers\Profesor\JustificationController;
use App\Http\Controllers\Profesor\ProfileController;
use App\Http\Controllers\Profesor\ScheduleController;
use App\Http\Controllers\Profesor\SessionController;
use App\Http\Controllers\Profesor\StudentController;
use Illuminate\Support\Facades\Route;

Route::prefix('profesor')
    ->name('profesor.')
    ->middleware(['governance.auth', 'role:profesor,tutor_academico'])
    ->group(function () {
        Route::get('/profile', [ProfileController::class, 'show'])->name('profile');
        Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');

        Route::get('/groups', [GroupController::class, 'index'])->name('groups.index');
        Route::get('/groups/{group}/students', [GroupController::class, 'students'])->name('groups.students');

        Route::get('/students/{student}', [StudentController::class, 'show'])->name('students.show');
        Route::get('/students/{student}/attendance', [StudentController::class, 'attendance'])->name('students.attendance');
        Route::get('/students/{student}/justifications', [StudentController::class, 'justifications'])->name('students.justifications');
        Route::post('/students/{student}/notify', [StudentController::class, 'notify'])->name('students.notify');
        Route::get('/justifications/{justification}', [JustificationController::class, 'show'])->name('justifications.show');

        Route::get('/schedule/today', [ScheduleController::class, 'today'])->name('schedule.today');
        Route::get('/schedule/{schedule}/session', [ScheduleController::class, 'sessionState'])->name('schedule.session');
        Route::get('/schedule/{schedule}/stream', [ScheduleController::class, 'stream'])->name('schedule.stream');
        Route::get('/schedule', [ScheduleController::class, 'week'])->name('schedule.week');

        Route::post('/sessions/open', [SessionController::class, 'open'])->name('sessions.open');
        Route::post('/sessions/{session}/nfc', [SessionController::class, 'nfc'])->name('sessions.nfc');
        Route::patch('/sessions/{session}/students/{student}', [SessionController::class, 'updateStudent'])->name('sessions.students.update');
        Route::post('/sessions/{session}/close', [SessionController::class, 'close'])->name('sessions.close');

        Route::get('/incidents/active', [IncidentController::class, 'active'])->name('incidents.active');
        Route::get('/incidents', [IncidentController::class, 'index'])->name('incidents.index');
        Route::get('/incidents/{incident}', [IncidentController::class, 'show'])->name('incidents.show');
        Route::post('/incidents', [IncidentController::class, 'store'])->name('incidents.store');
        Route::put('/incidents/{incident}', [IncidentController::class, 'update'])->name('incidents.update');
        Route::patch('/incidents/{incident}/students', [IncidentController::class, 'updateStudents'])->name('incidents.students.update');

        Route::get('/claims', [ClaimController::class, 'index'])->name('claims.index');
        Route::get('/claims/{claim}', [ClaimController::class, 'show'])->name('claims.show');
    });
