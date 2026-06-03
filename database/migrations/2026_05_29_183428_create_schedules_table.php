<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('schedules', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedSmallInteger('school_year_id');
            $table->foreign('school_year_id')->references('id')->on('school_years')->cascadeOnDelete();
            $table->unsignedMediumInteger('group_id');
            $table->foreign('group_id')->references('id')->on('groups')->cascadeOnDelete();
            $table->unsignedSmallInteger('subject_id');
            $table->foreign('subject_id')->references('id')->on('subjects')->cascadeOnDelete();
            $table->unsignedSmallInteger('teacher_id');
            $table->foreign('teacher_id')->references('id')->on('teachers')->cascadeOnDelete();
            $table->unsignedTinyInteger('classroom_id');
            $table->foreign('classroom_id')->references('id')->on('classroom')->cascadeOnDelete();
            $table->enum('day_of_week', ['MONDAY', 'TUESDAY', 'WEDNESDAY', 'THURSDAY', 'FRIDAY', 'SATURDAY', 'SUNDAY']);
            $table->time('start_time');
            $table->time('end_time');
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();

            $table->unique(
                ['group_id', 'subject_id', 'teacher_id', 'day_of_week', 'start_time', 'school_year_id'],
                'schedules_unique'
            );
            $table->index('school_year_id');
            $table->index('group_id');
            $table->index('teacher_id');
            $table->index('subject_id');
            $table->index('day_of_week');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('schedules');
    }
};
