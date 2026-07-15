<?php

declare(strict_types=1);

namespace TheOneDigi\TourSdk\Tests\Laravel;

use Illuminate\Support\Facades\Route;
use Illuminate\Testing\TestResponse;
use TheOneDigi\TourSdk\Laravel\Http\Middleware\VerifyTraveloWebhook;

class VerifyTraveloWebhookTest extends TestCase
{
    protected function defineRoutes($router): void
    {
        $router->post('/hooks', fn () => response()->json(['received' => true]))
            ->middleware(VerifyTraveloWebhook::class);
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function send(array $payload, ?string $signature = null, int|string|null $timestamp = null): TestResponse
    {
        // Encoded exactly as SendPartnerWebhookJob does upstream.
        $raw = (string) json_encode($payload);

        $server = ['CONTENT_TYPE' => 'application/json'];

        if ($signature !== null) {
            $server['HTTP_X_PARTNER_SIGNATURE'] = $signature;
        }

        if ($timestamp !== null) {
            $server['HTTP_X_PARTNER_TIMESTAMP'] = (string) $timestamp;
        }

        return $this->call('POST', '/hooks', [], [], [], $server, $raw);
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(): array
    {
        return [
            'event' => 'booking.confirmed',
            'order_code' => 'TB-1',
            'status' => 2,
            'timestamp' => time(),
        ];
    }

    private function sign(array $payload): string
    {
        return hash_hmac('sha256', (string) json_encode($payload), 'test_secret');
    }

    public function test_accepts_a_correctly_signed_webhook(): void
    {
        $payload = $this->payload();

        $this->send($payload, $this->sign($payload), time())
            ->assertOk()
            ->assertJson(['received' => true]);
    }

    public function test_rejects_an_unsigned_webhook(): void
    {
        $this->send($this->payload())->assertStatus(401)->assertJson(['error' => 'invalid_signature']);
    }

    public function test_rejects_a_wrong_secret(): void
    {
        $payload = $this->payload();

        $this->send($payload, hash_hmac('sha256', (string) json_encode($payload), 'other'), time())
            ->assertStatus(401);
    }

    public function test_rejects_a_tampered_body(): void
    {
        $signature = $this->sign($this->payload());

        $this->send(['event' => 'booking.confirmed', 'order_code' => 'TB-EVIL', 'status' => 2], $signature, time())
            ->assertStatus(401);
    }

    public function test_rejects_a_replay_outside_the_skew_window(): void
    {
        $payload = $this->payload();

        $this->send($payload, $this->sign($payload), time() - 301)->assertStatus(401);
        $this->send($payload, $this->sign($payload), time() - 299)->assertOk();
    }

    public function test_the_skew_window_is_configurable(): void
    {
        config(['travelo.webhook_max_skew_seconds' => 10]);
        $this->app->forgetInstance(\TheOneDigi\TourSdk\WebhookVerifier::class);

        $payload = $this->payload();

        $this->send($payload, $this->sign($payload), time() - 60)->assertStatus(401);
    }
}
