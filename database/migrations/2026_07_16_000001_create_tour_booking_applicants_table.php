<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Applicants as upstream returned them.
 *
 * `upstream_applicant_id` is kept because travelo-api addresses applicants by its
 * own id, not ours — updating one means naming theirs.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tour_booking_applicants', function (Blueprint $table) {
            $table->id();

            $table->foreignId('tour_booking_id')
                ->constrained('tour_bookings')
                ->cascadeOnDelete();

            $table->string('upstream_applicant_id')->nullable()->index();

            $table->json('payload')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tour_booking_applicants');
    }
};
