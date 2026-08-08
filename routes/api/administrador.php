<?php

use App\Http\Controllers\Administrador\AttendanceSettingController;
use App\Http\Controllers\Administrador\CareerController;
use App\Http\Controllers\Administrador\DeviceController;
use App\Http\Controllers\Administrador\GroupController;
use App\Http\Controllers\Administrador\NotificationController;
use App\Http\Controllers\Administrador\PermissionController;
use App\Http\Controllers\Administrador\SchoolYearController;
use App\Http\Controllers\Administrador\StudentController;
use App\Http\Controllers\Administrador\SubjectController;
use App\Http\Controllers\Administrador\TeacherController;
use Illuminate\Support\Facades\Route;

Route::prefix('administrador')->middleware(['governance.auth', 'role:administrador'])->group(function () {
    Route::apiResource('careers', CareerController::class);

    Route::get('school-years', [SchoolYearController::class, 'index']);
    Route::get('school-years/{schoolYear}', [SchoolYearController::class, 'show']);
    Route::post('school-years', [SchoolYearController::class, 'store']);
    Route::put('school-years/{schoolYear}', [SchoolYearController::class, 'update']);

    Route::apiResource('subjects', SubjectController::class);
    Route::apiResource('groups', GroupController::class);

    Route::get('devices/{device}/ping', [DeviceController::class, 'ping']);
    Route::apiResource('devices', DeviceController::class);

    Route::apiResource('attendance-settings', AttendanceSettingController::class);

    Route::apiResource('students', StudentController::class);
    Route::post('students/{student}/tutors', [StudentController::class, 'addTutor']);
    Route::put('students/{student}/tutors/{tutor}', [StudentController::class, 'updateTutor']);
    Route::delete('students/{student}/tutors/{tutor}', [StudentController::class, 'removeTutor']);

    Route::patch('teachers/{teacher}/academic-tutor', [TeacherController::class, 'toggleAcademicTutor']);
    Route::apiResource('teachers', TeacherController::class);

    Route::get('users/permissions', [PermissionController::class, 'index']);
    Route::get('users/{user}/permissions', [PermissionController::class, 'show']);
    Route::post('users/{user}/permissions/override', [PermissionController::class, 'storeOverride']);
    Route::delete('users/{user}/permissions/override/{override}', [PermissionController::class, 'destroyOverride']);

    Route::post('notifications/{notification}/resend', [NotificationController::class, 'resend']);
    Route::get('notifications', [NotificationController::class, 'index']);
    Route::get('notifications/{notification}', [NotificationController::class, 'show']);
    Route::post('notifications', [NotificationController::class, 'store']);
});
