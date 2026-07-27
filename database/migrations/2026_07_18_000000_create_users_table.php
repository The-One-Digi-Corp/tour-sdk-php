<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * travelo-api's `users` table, column for column.
 *
 * Skipped when the host app already has one. Every Laravel app ships a `users`
 * table of its own, and a package has no business replacing the table its host
 * authenticates against — the host's shape wins, and this migration exists only
 * for an app that has none.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('users')) {
            return;
        }

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->date('date_of_birth')->nullable();
            $table->unsignedSmallInteger('dial_code')->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('avatar')->nullable();
            $table->unsignedTinyInteger('gender')->nullable()->comment('1: Male | 2: Female');
            $table->timestamp('last_login')->nullable();
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->boolean('is_active')->default(true);
            $table->rememberToken();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
