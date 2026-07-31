<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->mediumIncrements('id');
            $table->unsignedTinyInteger('role_id');
            $table->foreign('role_id')->references('id')->on('roles');
            $table->unsignedBigInteger('governance_user_id')->nullable()->unique();
            $table->unsignedMediumInteger('group_id')->nullable()->index();
            $table->string('first_name', 45);
            $table->string('second_name', 45)->nullable();
            $table->string('first_surname', 45);
            $table->string('second_surname', 45);
            $table->string('email', 155)->unique();
            $table->string('password', 255);
            $table->dateTime('verified_at')->nullable();
            $table->boolean('active')->default(true)->index();
            $table->string('photo', 255);
            $table->string('phone', 10);
            $table->date('birth_date');
            $table->enum('gender', ['M', 'F', 'OTRO']);
            $table->timestamps();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->unsignedMediumInteger('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');
    }
};
