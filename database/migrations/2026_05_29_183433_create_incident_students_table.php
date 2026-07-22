<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('incident_students', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedSmallInteger('incident_id');
            $table->foreign('incident_id')->references('id')->on('incidents')->cascadeOnDelete();
            $table->unsignedMediumInteger('student_id');
            $table->foreign('student_id')->references('id')->on('users')->cascadeOnDelete();
            $table->unsignedMediumInteger('checked_by_user_id');
            $table->foreign('checked_by_user_id')->references('id')->on('users')->restrictOnDelete();
            $table->enum('status', ['DESCONOCIDO', 'PRESENTE', 'EXTRAVIADO', 'AUSENTE', 'SEGURO'])->default('DESCONOCIDO')->index();
            $table->dateTime('checked_at');
            $table->string('notes', 255)->nullable();
            $table->timestamps();

            $table->unique(['incident_id', 'student_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('incident_students');
    }
};
