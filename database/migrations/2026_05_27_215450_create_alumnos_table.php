<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alumnos', function (Blueprint $table) {
            $table->id();
            // grupo_id FK se agrega en la migración de grupos
            $table->foreignId('usuario_id')->nullable()->constrained('usuarios')->nullOnDelete();
            $table->unsignedBigInteger('grupo_id')->nullable()->index();
            $table->string('matricula', 20)->unique();
            $table->string('nombre', 100);
            $table->string('apellido_paterno', 100);
            $table->string('apellido_materno', 100)->nullable();
            $table->string('foto', 255)->nullable();
            $table->string('telefono', 20)->nullable();
            $table->text('direccion')->nullable();
            $table->date('fecha_nacimiento')->nullable();
            $table->string('genero', 20)->nullable();
            $table->string('nfc_uid', 100)->nullable()->unique();
            $table->uuid('qr_uuid')->nullable()->unique();
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->index('activo');
            $table->index('matricula');
            $table->index('nfc_uid');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alumnos');
    }
};
