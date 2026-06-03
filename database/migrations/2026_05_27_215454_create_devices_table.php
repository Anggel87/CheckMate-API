<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('devices', function (Blueprint $table) {
            $table->smallIncrements('id');
            $table->string('mac_address', 20)->unique();
            $table->string('ip', 30)->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedTinyInteger('classroom_id');
            $table->foreign('classroom_id')->references('id')->on('classroom')->cascadeOnDelete();
            $table->timestamps();

            $table->index('classroom_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('devices');
    }
};
