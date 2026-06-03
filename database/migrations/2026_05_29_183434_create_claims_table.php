<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('claims', function (Blueprint $table) {
            $table->mediumIncrements('id');
            $table->unsignedInteger('attendance_id');
            $table->foreign('attendance_id')->references('id')->on('attendances')->cascadeOnDelete();
            $table->unsignedMediumInteger('reviewed_by_user_id');
            $table->foreign('reviewed_by_user_id')->references('id')->on('users')->restrictOnDelete();
            $table->string('description', 255);
            $table->string('evidence', 255)->nullable();
            $table->enum('status', ['PENDING', 'ACCEPTED', 'REJECTED'])->default('PENDING')->index();
            $table->timestamps();

            $table->index('attendance_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('claims');
    }
};
