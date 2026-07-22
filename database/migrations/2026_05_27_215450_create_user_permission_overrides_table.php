<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_permission_overrides', function (Blueprint $table) {
            $table->smallIncrements('id');
            $table->unsignedMediumInteger('users_id');
            $table->foreign('users_id')->references('id')->on('users')->cascadeOnDelete();
            $table->unsignedTinyInteger('permissions_id');
            $table->foreign('permissions_id')->references('id')->on('permissions')->cascadeOnDelete();
            $table->enum('type', ['PERMITIR', 'DENEGAR']);
            $table->timestamps();

            $table->unique(['users_id', 'permissions_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_permission_overrides');
    }
};
