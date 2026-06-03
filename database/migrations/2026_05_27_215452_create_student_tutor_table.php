<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_tutor', function (Blueprint $table) {
            $table->mediumIncrements('id');
            $table->unsignedMediumInteger('tutor_id');
            $table->foreign('tutor_id')->references('id')->on('tutors')->cascadeOnDelete();
            $table->unsignedMediumInteger('student_id');
            $table->foreign('student_id')->references('id')->on('students')->cascadeOnDelete();
            $table->string('relationship', 50);
            $table->boolean('is_primary')->default(false);
            $table->boolean('receives_notifications')->default(true);
            $table->timestamps();

            $table->unique(['student_id', 'tutor_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_tutor');
    }
};
