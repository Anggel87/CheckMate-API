<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tutores', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 100);
            $table->string('apellido_paterno', 100);
            $table->string('apellido_materno', 100)->nullable();
            $table->string('telefono', 20)->nullable();
            $table->string('correo', 150)->nullable()->unique();
            $table->text('direccion')->nullable();
            $table->string('parentesco', 50)->nullable();
            $table->boolean('recibe_notificaciones')->default(true);
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tutores');
    }
};
