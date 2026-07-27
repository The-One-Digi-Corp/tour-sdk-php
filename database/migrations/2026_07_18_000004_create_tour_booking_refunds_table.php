<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Refund request against a booking, column for column with travelo-api.
 *
 * The workflow itself stays upstream: `status` and `refund_total` are copies of
 * travelo-api's decision, never a judgement made here.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('tour_booking_refunds')) {
            return;
        }

        Schema::create('tour_booking_refunds', function (Blueprint $table) {
            $table->id();

            $table->foreignId('tour_booking_id')
                ->constrained('tour_bookings')
                ->cascadeOnDelete();

            $table->text('reasons')->nullable();
            $table->text('feedback_staff')->nullable();
            $table->text('feedback_manager')->nullable();
            $table->decimal('refund_total', 15, 2)->unsigned()->default(0);
            $table->string('currency', 3)->default('USD')->comment('Canonical storage unit — USD/VND');
            $table->unsignedTinyInteger('status')->default(1)->comment('1: New | 2: Pending | 3: Approved | 4: Disapproved');

            $table->string('upstream_tour_booking_refund_id')->comment('ID of the refund request in the upstream system')->index();
            $table->string('note')->nullable()->comment('Note from the upstream system');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tour_booking_refunds');
    }
};
