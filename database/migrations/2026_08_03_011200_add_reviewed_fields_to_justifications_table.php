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
        Schema::table('justifications', function (Blueprint $table) {
            $table->unsignedMediumInteger('reviewed_by_user_id')->nullable()->after('status');
            $table->foreign('reviewed_by_user_id')->references('id')->on('users')->restrictOnDelete();
            $table->dateTime('reviewed_at')->nullable()->after('reviewed_by_user_id');
            $table->string('comment', 300)->nullable()->after('reviewed_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('justifications', function (Blueprint $table) {
            $table->dropForeign(['reviewed_by_user_id']);
            $table->dropColumn(['reviewed_by_user_id', 'reviewed_at', 'comment']);
        });
    }
};
