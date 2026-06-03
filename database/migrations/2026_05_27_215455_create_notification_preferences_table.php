<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_preferences', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedMediumInteger('tutor_id')->unique();
            $table->foreign('tutor_id')->references('id')->on('tutors')->cascadeOnDelete();
            $table->boolean('absences')->default(true);
            $table->boolean('lates')->default(true);
            $table->boolean('incidents')->default(true);
            $table->boolean('justifications')->default(true);
            $table->boolean('claims')->default(true);
            $table->boolean('announcements')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_preferences');
    }
};
