<?php

declare(strict_types=1);

namespace TheOneDigi\TourSdk\Laravel\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use TheOneDigi\TourSdk\Laravel\Models\TourBooking;

/**
 * Confirms to the customer that their booking was created — the counterpart to
 * CustomerAccountCreated, and likewise owned by this SDK now rather than
 * travelo-api. Sent for every successful booking, once the mirror row exists.
 *
 * The view is a package default under the `travelo::` namespace — a consumer that
 * wants its own branding publishes and overrides `emails/booking-created` rather
 * than replacing this class. `$booking` (with its `detail` relation loaded) is
 * public, so the view reads it directly; the customer-facing amount is the frozen
 * `input_total` in `input_currency`.
 */
class BookingCreated extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public readonly TourBooking $booking,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your booking is confirmed',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'travelo::emails.booking-created',
        );
    }
}
