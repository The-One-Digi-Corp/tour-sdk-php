<?php

declare(strict_types=1);

namespace TheOneDigi\TourSdk\Laravel\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use TheOneDigi\TourSdk\Laravel\Mail\CustomerAccountCreated;
use TheOneDigi\TourSdk\Laravel\Support\BookingOwner;

/**
 * Turns a booking's contact details into the account that owns it.
 *
 * travelo-api used to do this: on every partner booking it looked the customer up
 * by email, created an account with a generated password if none existed, and
 * emailed it to them. That now lives here, on the app the customer actually signs
 * into — travelo-api keeps only its own internal record.
 *
 * A logged-in customer owns the booking outright, so no lookup happens for them. A
 * returning customer (email already on file) is reused, and only a genuinely new
 * account is emailed its password. The email is handed back rather than sent here,
 * because a welcome mail must wait until the booking it welcomes has committed.
 */
class CustomerProvisioner
{
    public function __construct(
        private readonly bool $createCustomer = true,
        private readonly ?string $userModel = null,
    ) {}

    /**
     * @param array{name?: string|null, email?: string|null} $contact
     * @return array{id: int|string|null, welcome_email: ?string, welcome_mail: ?CustomerAccountCreated}
     */
    public function resolve(array $contact): array
    {
        // A logged-in customer is already the owner; never touch accounts for them.
        $ownerId = BookingOwner::id();
        if ($ownerId !== null) {
            return $this->result($ownerId);
        }

        $email = isset($contact['email']) ? trim((string) $contact['email']) : '';

        if (! $this->createCustomer || $email === '') {
            return $this->result(null);
        }

        $model = $this->userModel();

        $existing = $model::query()->where('email', $email)->first();
        if ($existing !== null) {
            return $this->result($existing->getKey());
        }

        $password = Str::password(12);

        try {
            $user = $model::query()->create([
                'name' => (string) ($contact['name'] ?? $email),
                'email' => $email,
                'password' => Hash::make($password),
            ]);
        } catch (\Throwable $e) {
            // A concurrent guest checkout with the same email won the unique index
            // between the lookup and the insert. Reuse the row it created rather
            // than failing a booking over a duplicate account.
            $raced = $model::query()->where('email', $email)->first();

            if ($raced !== null) {
                return $this->result($raced->getKey());
            }

            throw $e;
        }

        return [
            'id' => $user->getKey(),
            'welcome_email' => $email,
            'welcome_mail' => new CustomerAccountCreated($user, $password),
        ];
    }

    /**
     * @return array{id: int|string|null, welcome_email: null, welcome_mail: null}
     */
    private function result(int|string|null $id): array
    {
        return ['id' => $id, 'welcome_email' => null, 'welcome_mail' => null];
    }

    /**
     * @return class-string<Model>
     */
    private function userModel(): string
    {
        /** @var class-string<Model>|null $model */
        $model = $this->userModel ?: config('auth.providers.users.model');

        if (! is_string($model) || ! is_subclass_of($model, Model::class)) {
            throw new \RuntimeException(
                'Travelo SDK cannot create customer accounts: set travelo.account.user_model '
                . 'or config(auth.providers.users.model) to an Eloquent model.'
            );
        }

        return $model;
    }
}
