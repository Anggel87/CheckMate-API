<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->unsignedMediumInteger('student_id')->nullable()->change();
            $table->unsignedMediumInteger('tutor_id')->nullable()->change();
        });

        Schema::table('notifications', function (Blueprint $table) {
            $table->enum('recipient_type', ['TUTOR', 'STUDENT', 'TEACHER'])->default('TUTOR')->after('user_id');
            $table->index('recipient_type');
        });
    }

    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropIndex(['recipient_type']);
            $table->dropColumn('recipient_type');
        });

        Schema::table('notifications', function (Blueprint $table) {
            $table->unsignedMediumInteger('student_id')->nullable(false)->change();
            $table->unsignedMediumInteger('tutor_id')->nullable(false)->change();
        });
    }
};
