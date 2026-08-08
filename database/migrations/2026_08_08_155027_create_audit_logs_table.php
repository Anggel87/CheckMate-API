<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->string('entity', 20);
            $table->unsignedMediumInteger('entity_id');
            $table->enum('action', ['CREATE', 'UPDATE', 'DELETE']);
            $table->unsignedMediumInteger('performed_by_user_id')->nullable();
            $table->foreign('performed_by_user_id')->references('id')->on('users')->restrictOnDelete();
            $table->json('before')->nullable();
            $table->json('after')->nullable();
            $table->timestamps();

            $table->index(['entity', 'entity_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
