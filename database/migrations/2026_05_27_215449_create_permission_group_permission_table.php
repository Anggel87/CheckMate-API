<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('permission_group_permission', function (Blueprint $table) {
            $table->unsignedTinyInteger('permissions_id');
            $table->foreign('permissions_id')->references('id')->on('permissions')->cascadeOnDelete();
            $table->unsignedTinyInteger('permission_groups_id');
            $table->foreign('permission_groups_id')->references('id')->on('permission_groups')->cascadeOnDelete();

            $table->primary(['permissions_id', 'permission_groups_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('permission_group_permission');
    }
};
