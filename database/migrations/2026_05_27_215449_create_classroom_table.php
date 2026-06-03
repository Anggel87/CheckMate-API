<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('classroom', function (Blueprint $table) {
            $table->tinyIncrements('id');
            $table->string('name', 45);
            $table->string('building', 45);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('classroom');
    }
};
