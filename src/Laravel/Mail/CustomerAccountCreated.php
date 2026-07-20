<?php

declare(strict_types=1);

namespace TheOneDigi\TourSdk\Laravel\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Welcomes a customer whose account this SDK created during checkout, and hands
 * them the generated password so they can sign in.
 *
 * The view is a package default under the `travelo::` namespace — a consumer that
 * wants its own branding publishes and overrides `emails/customer-account-created`
 * rather than replacing this class. `$user` and `$plainPassword` are public, so the
 * view reads them directly.
 */
class CustomerAccountCreated extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public readonly Model $user,
        public readonly string $plainPassword,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your account is ready',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'travelo::emails.customer-account-created',
        );
    }
}
