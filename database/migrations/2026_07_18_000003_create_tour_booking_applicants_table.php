<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Travellers on the booking, column for column with travelo-api.
 *
 * The two trailing columns are the mirror's own. `upstream_applicant_id` is kept
 * because travelo-api addresses an applicant by its id, not ours — updating one
 * means naming theirs — and `payload` holds the row as upstream returned it.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('tour_booking_applicants')) {
            return;
        }

        Schema::create('tour_booking_applicants', function (Blueprint $table) {
            $table->id();

            $table->foreignId('tour_booking_id')
                ->constrained('tour_bookings')
                ->cascadeOnDelete();

            $table->unsignedTinyInteger('type')->comment('1: Adult | 2: Child | 3: Infant');
            $table->string('full_name');
            $table->unsignedTinyInteger('gender')->nullable()->comment('1: Male | 2: Female');
            $table->date('date_of_birth')->nullable();
            $table->string('nationality', 40)->nullable();
            $table->string('passport_photo')->nullable();

            $table->timestamps();

            // ---- Mirror-only ----
            $table->string('upstream_applicant_id')->nullable()->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tour_booking_applicants');
    }
};
