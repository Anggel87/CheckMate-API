<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('career_subject', function (Blueprint $table) {
            $table->unsignedTinyInteger('career_id');
            $table->foreign('career_id')->references('id')->on('careers')->cascadeOnDelete();
            $table->unsignedSmallInteger('subject_id');
            $table->foreign('subject_id')->references('id')->on('subjects')->cascadeOnDelete();

            $table->primary(['career_id', 'subject_id']);
            $table->index('career_id');
            $table->index('subject_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('career_subject');
    }
};
