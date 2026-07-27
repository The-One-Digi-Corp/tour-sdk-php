<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The partner-side mirror of a booking held on travelo-api, column for column.
 *
 * Every amount is copied verbatim from the upstream response — travelo-api is
 * authoritative on price, discount and commission, and arithmetic of our own
 * would be a second opinion about a number the customer is charged.
 *
 * Two deliberate departures from the upstream DDL, both forced:
 *
 *  - `partner_id` carries no foreign key. Upstream points it at `partners`, a
 *    table that does not exist in a partner's own database.
 *  - `user_id` is nullable where upstream has it NOT NULL. Guest checkout is a
 *    flow this SDK supports, and a guest booking has no user to point at.
 *
 * Columns below the upstream block are the mirror's own: they describe our call
 * to travelo-api, not the booking, so upstream has no equivalent.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('tour_bookings')) {
            return;
        }

        Schema::create('tour_bookings', function (Blueprint $table) {
            $table->id();
            $table->string('order_code', 10)->unique();

            // Nullable for guest checkout; indexed because every booking read
            // for an account filters on it.
            $table->unsignedInteger('user_id')->nullable()->index();

            $table->unsignedBigInteger('partner_id')->nullable();

            // Generated before the call, written with the row: on a timeout the
            // outcome is unknown, and replaying the same key returns the existing
            // booking upstream instead of holding a second seat.
            $table->string('idempotency_key', 80)->nullable();

            $table->decimal('sub_total', 10, 2)->unsigned()->default(0);
            $table->decimal('discount', 10, 2)->unsigned()->default(0);
            $table->decimal('total', 10, 2)->unsigned()->default(0);
            $table->decimal('cost', 10, 2)->unsigned()->default(0);
            $table->string('currency', 3)->default('USD')->comment('Canonical storage unit — USD/VND');

            $table->decimal('input_sub_total', 16, 2)->unsigned()->default(0);
            $table->decimal('input_discount', 16, 2)->unsigned()->default(0);
            $table->decimal('input_total', 16, 2)->unsigned()->default(0);
            $table->decimal('input_cost', 16, 2)->unsigned()->default(0);
            $table->string('input_currency', 3)->default('USD')->comment('Currency the user selected on the UI');
            $table->unsignedInteger('input_currency_version')->default(1)->comment('currencies.version applied to the input currency');
            $table->decimal('input_currency_exchange_rate', 16, 8)->default(1)->comment('input_currency units per 1 USD at that version');

            $table->unsignedTinyInteger('status')->default(1)->comment('1: Pending payment | 2: In progress | 3: Completed | 4: Canceled | 5: Refunded');
            $table->unsignedSmallInteger('payment_gateway_id')->nullable();
            $table->string('promotion_code', 20)->nullable();

            $table->string('country_code', 2)->nullable();
            $table->string('country_name', 80)->nullable();
            $table->string('ip')->nullable();
            $table->string('device')->nullable();
            $table->string('os')->nullable();
            $table->string('name', 100)->nullable();
            $table->string('email', 50)->nullable();
            $table->string('email2', 50)->nullable();
            $table->unsignedSmallInteger('dial_code')->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('browser', 30)->nullable();
            $table->string('card_no', 20)->nullable();

            $table->timestamp('paid_at')->nullable();
            $table->unsignedInteger('assign_to')->nullable();
            $table->boolean('deleted')->default(false);
            $table->timestamp('deleted_at')->nullable();
            $table->timestamps();

            // ---- Mirror-only ----
            $table->string('upstream_tour_booking_id')->nullable()->index();

            $table->unique(['partner_id', 'idempotency_key']);

            // Upstream's composite unique cannot dedupe here: `partner_id` is null
            // in a partner's own database, and MySQL treats every null as distinct.
            // This is the index that actually stops a replay from booking twice.
            $table->unique('idempotency_key');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tour_bookings');
    }
};
