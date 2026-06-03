<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_settings', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('schedule_id')->unique();
            $table->foreign('schedule_id')->references('id')->on('schedules')->cascadeOnDelete();
            $table->unsignedTinyInteger('present_tolerance_minutes')->default(10);
            $table->unsignedTinyInteger('late_tolerance_minutes')->default(30);
            $table->boolean('allow_manual_attendance')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_settings');
    }
};
