<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('addresses', function (Blueprint $table) {
            $table->mediumIncrements('id');
            $table->string('street', 90);
            $table->string('number', 31);
            $table->string('neighborhood', 70);
            $table->string('postal_code', 5);
            $table->string('city', 30);
            $table->string('state', 16);
            $table->string('country', 6);
            $table->unsignedMediumInteger('users_id');
            $table->foreign('users_id')->references('id')->on('users')->cascadeOnDelete();
            $table->unsignedMediumInteger('tutors_id')->nullable();
            $table->foreign('tutors_id')->references('id')->on('tutors')->nullOnDelete();
            $table->timestamps();

            $table->index('users_id');
            $table->index('tutors_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('addresses');
    }
};
