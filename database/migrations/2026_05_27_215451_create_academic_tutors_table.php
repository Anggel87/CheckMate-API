<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('academic_tutors', function (Blueprint $table) {
            $table->smallIncrements('id');
            $table->unsignedSmallInteger('teacher_id')->unique();
            $table->foreign('teacher_id')->references('id')->on('teachers')->cascadeOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('academic_tutors');
    }
};
