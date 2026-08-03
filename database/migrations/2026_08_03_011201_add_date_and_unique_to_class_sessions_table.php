<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('class_sessions', function (Blueprint $table) {
            $table->date('date')->nullable()->after('schedule_id');
        });

        DB::statement('UPDATE class_sessions SET date = DATE(opened_at)');

        // Se deja `date` nullable a nivel de columna: no hay doctrine/dbal instalado para
        // usar ->change() y pasarla a NOT NULL, y `ALTER ... MODIFY` no es portable entre
        // MySQL y SQLite (motor de los tests). La aplicación siempre la rellena al crear
        // una sesión (ver OpenClassSessionService); lo que sí importa para SES01 es el
        // índice único de abajo.
        Schema::table('class_sessions', function (Blueprint $table) {
            $table->unique(['schedule_id', 'date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('class_sessions', function (Blueprint $table) {
            $table->dropUnique(['schedule_id', 'date']);
            $table->dropColumn('date');
        });
    }
};
