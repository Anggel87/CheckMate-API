<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('groups', function (Blueprint $table) {
            $table->mediumIncrements('id');
            $table->unsignedSmallInteger('school_year_id');
            $table->foreign('school_year_id')->references('id')->on('school_years')->cascadeOnDelete();
            $table->unsignedTinyInteger('career_id');
            $table->foreign('career_id')->references('id')->on('careers')->cascadeOnDelete();
            $table->string('section', 5);
            $table->string('grade', 5);
            $table->enum('shift', ['MATUTINO', 'VESPERTINO', 'INGENIERIA'])->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();

            $table->unique(['school_year_id', 'career_id', 'grade', 'section']);
            $table->index('school_year_id');
            $table->index('career_id');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreign('group_id')->references('id')->on('groups')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['group_id']);
        });

        Schema::dropIfExists('groups');
    }
};
