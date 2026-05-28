<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // El tutor académico siempre es un docente con responsabilidades de seguimiento grupal
        Schema::create('tutores_academicos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('docente_id')->unique()->constrained('docentes')->cascadeOnDelete();
            $table->boolean('activo')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tutores_academicos');
    }
};
