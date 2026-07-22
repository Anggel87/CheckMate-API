<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('claims', function (Blueprint $table) {
            $table->mediumIncrements('id');
            $table->unsignedInteger('attendance_id');
            $table->foreign('attendance_id')->references('id')->on('attendances')->cascadeOnDelete();
            $table->unsignedMediumInteger('tutor_id');
            $table->foreign('tutor_id')->references('id')->on('users')->cascadeOnDelete();
            $table->unsignedMediumInteger('director_id');
            $table->foreign('director_id')->references('id')->on('users')->restrictOnDelete();
            $table->string('description', 255);
            $table->string('evidence', 255)->nullable();
            $table->enum('status', ['PENDIENTE', 'ACEPTADO', 'RECHAZADO'])->default('PENDIENTE')->index();
            $table->timestamps();

            $table->index('attendance_id');
            $table->index('tutor_id');
            $table->index('director_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('claims');
    }
};
