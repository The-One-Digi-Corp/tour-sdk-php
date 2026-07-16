<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The partner-side mirror of a booking held on travelo-api.
 *
 * Only the columns the SDK itself queries are promoted to real columns; whatever
 * else upstream returned lives in `upstream_payload`. travelo-api is authoritative
 * on price, discount and commission, so nothing here is computed locally — every
 * amount is copied verbatim from the response. That is also why there is no
 * currency version or exchange rate: converting money is upstream's job, not the
 * mirror's.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tour_bookings', function (Blueprint $table) {
            $table->id();

            // The shared key between their reservation and our row.
            $table->string('order_code')->unique();

            // Written with the row, generated before the call: on a timeout the
            // outcome is unknown, and replaying the same key returns the existing
            // booking upstream instead of holding a second seat.
            $table->string('idempotency_key')->nullable()->unique();

            // The consuming app's user. Nullable because guest checkout is upstream's
            // to allow; indexed because every booking read filters on it.
            $table->unsignedBigInteger('user_id')->nullable()->index();

            $table->unsignedTinyInteger('status')->default(1);

            // Guards the confirm call: a payment return URL and a gateway IPN can
            // both fire for one booking. Unused until payment lands (Đợt 1b).
            $table->unsignedTinyInteger('api_status')->default(0);
            $table->json('api_response')->nullable();

            $table->decimal('total', 15, 2)->default(0);
            $table->char('currency', 3);
            $table->decimal('input_total', 15, 2)->nullable();
            $table->char('input_currency', 3)->nullable();

            // Mirrors travelo-api's hold TTL. After this the seat is gone upstream
            // whether or not this row still says otherwise.
            $table->timestamp('held_until')->nullable()->index();

            $table->json('upstream_payload')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tour_bookings');
    }
};
