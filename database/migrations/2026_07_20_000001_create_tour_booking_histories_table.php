<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Creates the tour_booking_histories table — an audit log of every
     * notable action on a booking (created, quoted, confirmed, canceled,
     * refunded, applicant updated, etc.). Mirrors the admin schema so
     * that SDK-mode apps (e.g. be-travelo-partner) can write and query
     * the same history rows the admin panel does.
     *
     * Foreign keys are intentionally omitted as per SDK convention:
     * tables like tour_bookings and users may reside in a different
     * database schema (e.g. travelo_partner_db), and MySQL cannot
     * enforce cross-schema FK constraints from a migration. Data
     * integrity is enforced at the application / Eloquent level.
     */
    public function up(): void
    {
        if (Schema::hasTable('tour_booking_histories')) {
            return;
        }

        Schema::create('tour_booking_histories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tour_booking_id')->index();
            $table->unsignedBigInteger('user_id')->index();
            $table->string('action');
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index('action');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tour_booking_histories');
    }
};
