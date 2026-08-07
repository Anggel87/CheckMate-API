<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedMediumInteger('student_id');
            $table->foreign('student_id')->references('id')->on('users')->cascadeOnDelete();
            $table->unsignedMediumInteger('tutor_id');
            $table->foreign('tutor_id')->references('id')->on('tutors')->cascadeOnDelete();
            $table->unsignedMediumInteger('user_id')->nullable();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->string('title', 90);
            $table->string('message', 350);
            $table->enum('type', ['INASISTENCIA', 'RETARDO', 'INCIDENTE', 'JUSTIFICANTE', 'RECLAMO', 'AVISO', 'RECLAMO_PROFESOR']);
            $table->boolean('is_read')->default(false)->index();
            $table->dateTime('sent_at')->nullable();
            $table->timestamps();

            $table->index('student_id');
            $table->index('tutor_id');
            $table->index('user_id');
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
