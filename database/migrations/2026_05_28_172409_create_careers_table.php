<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('careers', function (Blueprint $table) {
            $table->tinyIncrements('id');
            $table->string('name', 150);
            $table->string('short_name', 20)->nullable();
            $table->string('code', 30);
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedSmallInteger('directors_id');
            $table->foreign('directors_id')->references('id')->on('directors')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('careers');
    }
};
