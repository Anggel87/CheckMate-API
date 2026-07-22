<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('incidents', function (Blueprint $table) {
            $table->smallIncrements('id');
            $table->unsignedMediumInteger('reported_by_user_id');
            $table->foreign('reported_by_user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->unsignedInteger('schedule_id');
            $table->foreign('schedule_id')->references('id')->on('schedules')->cascadeOnDelete();
            $table->string('title', 255)->nullable();
            $table->string('description', 255)->nullable();
            $table->enum('severity', ['BAJA', 'MEDIA', 'ALTA', 'CRITICA'])->nullable();
            $table->string('evidence', 255)->nullable();
            $table->dateTime('incident_at');
            $table->enum('status', ['ACTIVO', 'EN_REVISION', 'RESUELTO', 'CANCELADO'])->default('ACTIVO')->index();
            $table->unsignedMediumInteger('reviewed_by_user_id');
            $table->foreign('reviewed_by_user_id')->references('id')->on('users')->restrictOnDelete();
            $table->string('type', 25);
            $table->timestamps();

            $table->index('schedule_id');
            $table->index('reported_by_user_id');
            $table->index('reviewed_by_user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('incidents');
    }
};
