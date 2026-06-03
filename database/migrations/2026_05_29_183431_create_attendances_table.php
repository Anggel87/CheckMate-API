<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendances', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedMediumInteger('student_id');
            $table->foreign('student_id')->references('id')->on('students')->cascadeOnDelete();
            $table->unsignedInteger('schedule_id');
            $table->foreign('schedule_id')->references('id')->on('schedules')->cascadeOnDelete();
            $table->unsignedSmallInteger('devices_id');
            $table->foreign('devices_id')->references('id')->on('devices')->restrictOnDelete();
            $table->dateTime('registered_at');
            $table->enum('status', ['PRESENT', 'LATE', 'ABSENT', 'JUSTIFIED']);
            $table->enum('method', ['NFC', 'QR', 'MANUAL', 'SYSTEM']);
            $table->timestamps();

            $table->index('student_id');
            $table->index('schedule_id');
            $table->index('registered_at');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
