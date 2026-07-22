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
            $table->unsignedInteger('class_session_id');
            $table->foreign('class_session_id')->references('id')->on('class_sessions')->cascadeOnDelete();
            $table->unsignedMediumInteger('student_id');
            $table->foreign('student_id')->references('id')->on('users')->cascadeOnDelete();
            $table->unsignedInteger('schedule_id');
            $table->foreign('schedule_id')->references('id')->on('schedules')->cascadeOnDelete();
            $table->unsignedSmallInteger('devices_id');
            $table->foreign('devices_id')->references('id')->on('devices')->restrictOnDelete();
            $table->dateTime('registered_at');
            $table->enum('status', ['PRESENTE', 'RETARDO', 'FALTA', 'JUSTIFICADA']);
            $table->enum('method', ['NFC', 'QR', 'MANUAL', 'SISTEMA']);
            $table->timestamps();

            $table->index('class_session_id');
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
