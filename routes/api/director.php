<?php

use App\Http\Controllers\Director\AuditLogController;
use App\Http\Controllers\Director\ChartController;
use App\Http\Controllers\Director\ClaimController;
use App\Http\Controllers\Director\DeviceController;
use App\Http\Controllers\Director\GroupController;
use App\Http\Controllers\Director\IncidentController;
use App\Http\Controllers\Director\StudentController;
use App\Http\Controllers\Director\TeacherController;
use Illuminate\Support\Facades\Route;

Route::prefix('director-carrera')->middleware(['governance.auth', 'role:director_carrera'])->group(function () {
    Route::get('groups', [GroupController::class, 'index']);
    Route::get('groups/{group}', [GroupController::class, 'show']);
    Route::get('groups/{group}/students', [GroupController::class, 'students']);
    Route::get('groups/{group}/schedule', [GroupController::class, 'schedule']);

    Route::get('students/{student}', [StudentController::class, 'show']);
    Route::get('students/{student}/attendance', [StudentController::class, 'attendance']);
    Route::get('students/{student}/justifications', [StudentController::class, 'justifications']);

    Route::get('teachers', [TeacherController::class, 'index']);
    Route::get('teachers/{teacher}', [TeacherController::class, 'show']);
    Route::get('teachers/{teacher}/class-attendance', [TeacherController::class, 'classAttendance']);

    Route::get('incidents/active', [IncidentController::class, 'active']);
    Route::get('incidents', [IncidentController::class, 'index']);
    Route::get('incidents/{incident}', [IncidentController::class, 'show']);
    Route::post('incidents', [IncidentController::class, 'store']);
    Route::put('incidents/{incident}', [IncidentController::class, 'update']);
    Route::patch('incidents/{incident}/students', [IncidentController::class, 'updateStudents']);
    Route::post('incidents/{incident}/close', [IncidentController::class, 'close']);

    Route::get('claims', [ClaimController::class, 'index']);
    Route::get('claims/{claim}', [ClaimController::class, 'show']);
    Route::patch('claims/{claim}/action', [ClaimController::class, 'action']);

    Route::get('charts/summary', [ChartController::class, 'summary']);
    Route::get('charts/general', [ChartController::class, 'general']);
    Route::get('charts/incidents', [ChartController::class, 'incidents']);
    Route::get('charts/absences', [ChartController::class, 'absences']);
    Route::get('charts/justifications', [ChartController::class, 'justifications']);

    Route::get('devices', [DeviceController::class, 'index']);
    Route::get('devices/{device}/ping', [DeviceController::class, 'ping']);
    Route::get('devices/{device}', [DeviceController::class, 'show']);

    Route::get('logs/students', [AuditLogController::class, 'students']);
    Route::get('logs/teachers', [AuditLogController::class, 'teachers']);
    Route::get('logs/groups', [AuditLogController::class, 'groups']);
    Route::get('logs/devices', [AuditLogController::class, 'devices']);
    Route::get('logs/{log}', [AuditLogController::class, 'show']);
});
