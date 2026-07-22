<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('class_sessions', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('schedule_id');
            $table->foreign('schedule_id')->references('id')->on('schedules')->cascadeOnDelete();
            $table->unsignedMediumInteger('teacher_id');
            $table->foreign('teacher_id')->references('id')->on('users')->restrictOnDelete();
            $table->unsignedSmallInteger('device_id');
            $table->foreign('device_id')->references('id')->on('devices')->restrictOnDelete();
            $table->dateTime('opened_at');
            $table->dateTime('closed_at')->nullable();
            $table->enum('status', ['ABIERTA', 'CERRADA', 'CANCELADA'])->default('ABIERTA')->index();
            $table->enum('opening_method', ['NFC', 'QR', 'MANUAL']);
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();

            $table->index('schedule_id');
            $table->index('teacher_id');
            $table->index('device_id');
            $table->index('opened_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('class_sessions');
    }
};
