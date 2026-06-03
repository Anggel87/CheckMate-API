<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('group_academic_tutor', function (Blueprint $table) {
            $table->unsignedMediumInteger('group_id');
            $table->foreign('group_id')->references('id')->on('groups')->cascadeOnDelete();
            $table->unsignedSmallInteger('academic_tutor_id');
            $table->foreign('academic_tutor_id')->references('id')->on('academic_tutors')->cascadeOnDelete();
            $table->boolean('is_active')->default(true);
            $table->date('assigned_at');

            $table->primary(['group_id', 'academic_tutor_id']);
            $table->index('group_id');
            $table->index('academic_tutor_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('group_academic_tutor');
    }
};
