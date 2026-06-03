<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tutors', function (Blueprint $table) {
            $table->mediumIncrements('id');
            $table->string('first_name', 45);
            $table->string('second_name', 45)->nullable();
            $table->string('first_surname', 45);
            $table->string('second_surname', 45);
            $table->string('phone', 10);
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tutors');
    }
};
