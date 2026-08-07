<?php

use App\Http\Controllers\Administrador\CareerController;
use App\Http\Controllers\Administrador\DeviceController;
use App\Http\Controllers\Administrador\GroupController;
use App\Http\Controllers\Administrador\SchoolYearController;
use App\Http\Controllers\Administrador\SubjectController;
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
});
