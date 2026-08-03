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
        Schema::table('claims', function (Blueprint $table) {
            $table->unsignedMediumInteger('action_by_user_id')->nullable()->after('status');
            $table->foreign('action_by_user_id')->references('id')->on('users')->restrictOnDelete();
            $table->dateTime('action_at')->nullable()->after('action_by_user_id');
            $table->string('comment', 500)->nullable()->after('action_at');
        });

        // claims.status era un ENUM de 3 valores; las acciones exclusivas del tutor
        // académico necesitan 2 valores intermedios más (EN_PROCESO/CONTACTADO). No hay
        // doctrine/dbal instalado, así que no se puede usar ->change() para ampliar un
        // enum, y `ALTER ... MODIFY` no es portable entre MySQL y SQLite (motor de los
        // tests). Se reemplaza el ENUM por un string libre copiando los valores a mano
        // (mismo patrón ya usado en incidents.type), validado en la capa de aplicación.
        Schema::table('claims', function (Blueprint $table) {
            $table->string('status_text', 20)->nullable()->after('status');
        });

        DB::statement('UPDATE claims SET status_text = status');

        Schema::table('claims', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropColumn('status');
        });

        Schema::table('claims', function (Blueprint $table) {
            $table->string('status', 20)->default('PENDIENTE')->after('evidence')->index();
        });

        DB::statement('UPDATE claims SET status = status_text');

        Schema::table('claims', function (Blueprint $table) {
            $table->dropColumn('status_text');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("UPDATE claims SET status = 'ACEPTADO' WHERE status IN ('EN_PROCESO', 'CONTACTADO')");

        Schema::table('claims', function (Blueprint $table) {
            $table->dropForeign(['action_by_user_id']);
            $table->dropColumn(['action_by_user_id', 'action_at', 'comment']);
        });
    }
};
