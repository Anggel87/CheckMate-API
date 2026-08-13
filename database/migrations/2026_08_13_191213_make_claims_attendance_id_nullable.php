<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Reclamos "generales" (quejas no ligadas a una materia, ej. instalaciones)
     * no tienen una asistencia que reclamar, asi que attendance_id deja de ser
     * obligatorio.
     */
    public function up(): void
    {
        Schema::table('claims', function (Blueprint $table) {
            $table->unsignedInteger('attendance_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('claims', function (Blueprint $table) {
            $table->unsignedInteger('attendance_id')->nullable(false)->change();
        });
    }
};
