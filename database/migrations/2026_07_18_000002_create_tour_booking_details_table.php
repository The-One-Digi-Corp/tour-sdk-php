<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The booked tour line, column for column with travelo-api.
 *
 * `tour_id` and `tour_price_group_id` carry no foreign keys: upstream points them
 * at `tours` and `tour_price_groups`, catalog tables that live on travelo-api and
 * have no copy here. They stay indexed so a lookup by tour is still cheap.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('tour_booking_details')) {
            return;
        }

        Schema::create('tour_booking_details', function (Blueprint $table) {
            $table->id();

            $table->foreignId('tour_booking_id')
                ->constrained('tour_bookings')
                ->cascadeOnDelete();

            $table->unsignedBigInteger('tour_id')->index();
            $table->unsignedBigInteger('tour_price_group_id')->index();
            $table->date('departure_date')->nullable();

            $table->unsignedTinyInteger('adult_quantity')->default(0);
            $table->unsignedTinyInteger('child_quantity')->default(0);
            $table->unsignedTinyInteger('infant_quantity')->default(0);

            $table->decimal('adult_price', 10, 2)->default(0);
            $table->decimal('child_price', 10, 2)->default(0);
            $table->decimal('infant_price', 10, 2)->default(0);
            $table->decimal('discount_price', 10, 2)->default(0);
            $table->string('currency', 3)->default('USD');

            $table->decimal('input_adult_price', 16, 2)->default(0);
            $table->decimal('input_child_price', 16, 2)->default(0);
            $table->decimal('input_infant_price', 16, 2)->default(0);
            $table->decimal('input_discount_price', 16, 2)->default(0);
            $table->string('input_currency', 3)->default('USD');
            $table->unsignedInteger('input_currency_version')->default(1);
            $table->decimal('input_currency_exchange_rate', 16, 8)->default(1);

            $table->unsignedTinyInteger('discount_type')->default(0);
            $table->unsignedTinyInteger('discount_count')->default(0);
            $table->text('special_request')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tour_booking_details');
    }
};
