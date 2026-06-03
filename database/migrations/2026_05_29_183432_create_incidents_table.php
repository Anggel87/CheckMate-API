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
            $table->enum('severity', ['LOW', 'MEDIUM', 'HIGH', 'CRITICAL'])->nullable();
            $table->string('evidence', 255)->nullable();
            $table->dateTime('incident_at')->nullable();
            $table->timestamps();

            $table->index('schedule_id');
            $table->index('reported_by_user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('incidents');
    }
};
