<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds the upstream_tour_booking_id column for tracking the upstream booking.
 *
 * The main SDK migration (2026_07_18_000001) creates this column in its CREATE
 * TABLE statement, so fresh installs already have it. This migration exists for
 * apps that created the tour_bookings table before that column was added —
 * running a CREATE TABLE on an existing table would fail, so we ALTER instead.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('tour_bookings', 'upstream_tour_booking_id')) {
            Schema::table('tour_bookings', function (Blueprint $table) {
                $table->string('upstream_tour_booking_id')
                    ->nullable()
                    ->after('idempotency_key')
                    ->index();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('tour_bookings', 'upstream_tour_booking_id')) {
            Schema::table('tour_bookings', function (Blueprint $table) {
                $table->dropColumn('upstream_tour_booking_id');
            });
        }
    }
};
