<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('students', function (Blueprint $table) {
            $table->mediumIncrements('id');
            $table->unsignedMediumInteger('user_id');
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            // group_id FK added after groups migration runs
            $table->unsignedMediumInteger('group_id')->nullable()->index();
            $table->string('student_number', 20)->index();
            $table->string('nfc_uid', 100)->nullable()->index();
            $table->string('qr_uuid', 36)->nullable()->index();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};
