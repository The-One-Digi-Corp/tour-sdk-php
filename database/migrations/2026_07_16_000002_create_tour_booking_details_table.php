<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

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

            $table->unsignedInteger('tour_id')->nullable()->index();
            $table->unsignedInteger('tour_price_group_id')->nullable();
            $table->date('departure_date')->nullable();

            $table->unsignedSmallInteger('adult_quantity')->default(1);
            $table->unsignedSmallInteger('child_quantity')->default(0);
            $table->unsignedSmallInteger('infant_quantity')->default(0);

            $table->decimal('adult_price', 15, 2)->default(0);
            $table->decimal('child_price', 15, 2)->default(0);
            $table->decimal('infant_price', 15, 2)->default(0);
            $table->decimal('group_price', 15, 2)->default(0);
            $table->decimal('discount_price', 15, 2)->default(0);
            $table->unsignedTinyInteger('discount_type')->nullable();
            $table->unsignedSmallInteger('discount_count')->nullable();
            $table->text('special_request')->nullable();

            $table->char('base_currency', 3)->default('USD');

            $table->decimal('input_adult_price', 15, 2)->nullable();
            $table->decimal('input_child_price', 15, 2)->nullable();
            $table->decimal('input_infant_price', 15, 2)->nullable();
            $table->decimal('input_group_price', 15, 2)->nullable();
            $table->decimal('input_discount_price', 15, 2)->nullable();
            $table->char('input_currency', 3)->nullable();
            $table->unsignedInteger('input_currency_version')->nullable();
            $table->decimal('input_currency_exchange_rate', 15, 6)->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tour_booking_details');
    }
};
