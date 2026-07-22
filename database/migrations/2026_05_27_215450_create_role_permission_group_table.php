<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('role_permission_group', function (Blueprint $table) {
            $table->unsignedTinyInteger('role_id');
            $table->foreign('role_id')->references('id')->on('roles')->cascadeOnDelete();
            $table->unsignedTinyInteger('permission_group_id');
            $table->foreign('permission_group_id')->references('id')->on('permission_groups')->cascadeOnDelete();

            $table->primary(['role_id', 'permission_group_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('role_permission_group');
    }
};
