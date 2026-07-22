<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('justifications', function (Blueprint $table) {
            $table->mediumIncrements('id');
            $table->unsignedInteger('attendance_id');
            $table->foreign('attendance_id')->references('id')->on('attendances')->cascadeOnDelete();
            $table->unsignedMediumInteger('justified_by_user_id');
            $table->foreign('justified_by_user_id')->references('id')->on('users')->restrictOnDelete();
            $table->string('reason', 255);
            $table->string('file', 255)->nullable();
            $table->dateTime('justified_at');
            $table->enum('status', ['PENDIENTE', 'ACEPTADO', 'RECHAZADO'])->default('PENDIENTE')->index();
            $table->timestamps();

            $table->unique('attendance_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('justifications');
    }
};
