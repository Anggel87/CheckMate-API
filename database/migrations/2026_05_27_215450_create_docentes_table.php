<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('docentes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usuario_id')->unique()->constrained('usuarios')->cascadeOnDelete();
            $table->string('nombre', 100);
            $table->string('apellido_paterno', 100);
            $table->string('apellido_materno', 100)->nullable();
            $table->string('telefono', 20)->nullable();
            $table->string('foto', 255)->nullable();
            $table->string('especialidad', 150)->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->index('activo');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('docentes');
    }
};
