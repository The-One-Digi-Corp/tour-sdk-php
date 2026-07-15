<?php

declare(strict_types=1);

namespace TheOneDigi\TourSdk;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use TheOneDigi\TourSdk\Exception\ApiException;
use TheOneDigi\TourSdk\Exception\ConfigurationException;
use TheOneDigi\TourSdk\Exception\TransportException;
use TheOneDigi\TourSdk\Api\BookingApi;
use TheOneDigi\TourSdk\Api\TourApi;

/**
 * HTTP client for the Travelo Partner API.
 *
 * `$baseUrl` is the origin only ("http://localhost:8000"). Paths are passed as
 * travelo-api sees them ("api/partner/bookings") so the signed path matches the
 * server's `$request->path()` exactly. A trailing "/api" on the base is stripped
 * defensively, since existing .env files carry it.
 *
 * Not final: consumers type-hint this class directly and need to double it in
 * their own tests. Making it final pushes every consumer into writing a
 * pass-through wrapper of their own, which is duplication with no payoff.
 * Prefer injecting a fake Guzzle handler (see the `$http` argument) when you want
 * the signing and URL building exercised for real.
 */
class PartnerClient
{
    public const SDK_NAME = 'tour-sdk-php';
    public const SDK_VERSION = '0.1.0';

    private readonly PartnerSigner $signer;

    private readonly Client $http;

    private readonly string $baseUrl;

    public function __construct(
        string $baseUrl,
        string $clientId,
        string $secret,
        private readonly ?string $defaultCurrency = null,
        private readonly ?string $locale = null,
        float $timeout = 10.0,
        private readonly ?string $integrationName = null,
        private readonly ?string $integrationVersion = null,
        ?Client $http = null,
    ) {
        $this->baseUrl = self::normalizeBaseUrl($baseUrl);
        $this->signer = new PartnerSigner($clientId, $secret);
        $this->http = $http ?? new Client([
            'timeout' => $timeout,
            'connect_timeout' => min($timeout, 5.0),
            'http_errors' => false,
        ]);
    }

    /**
     * @param array<string, mixed> $config
     */
    public static function fromConfig(array $config, ?Client $http = null): self
    {
        foreach (['base_url', 'client_id', 'secret'] as $key) {
            if (empty($config[$key])) {
                throw new ConfigurationException("Missing partner API config key: {$key}");
            }
        }

        return new self(
            baseUrl: (string) $config['base_url'],
            clientId: (string) $config['client_id'],
            secret: (string) $config['secret'],
            defaultCurrency: $config['currency'] ?? null,
            locale: $config['locale'] ?? null,
            timeout: (float) ($config['timeout'] ?? 10.0),
            integrationName: $config['integration_name'] ?? null,
            integrationVersion: $config['integration_version'] ?? null,
            http: $http,
        );
    }

    public static function normalizeBaseUrl(string $baseUrl): string
    {
        $trimmed = rtrim(trim($baseUrl), '/');

        return preg_replace('#/api$#', '', $trimmed) ?? $trimmed;
    }

    public function bookings(): BookingApi
    {
        return new BookingApi($this);
    }

    public function tours(): TourApi
    {
        return new TourApi($this);
    }

    /**
     * @param array<string, mixed> $query
     * @param array<string, string> $headers
     * @return array<string, mixed>
     */
    public function get(string $path, array $query = [], array $headers = []): array
    {
        return $this->request('GET', $path, null, $query, $headers);
    }

    /**
     * @param array<string, mixed>|null $body
     * @param array<string, string> $headers
     * @return array<string, mixed>
     */
    public function post(string $path, ?array $body = null, array $headers = []): array
    {
        return $this->request('POST', $path, $body, [], $headers);
    }

    /**
     * Unwraps travelo-api's {status, message, errors, data} envelope.
     *
     * @param array<string, mixed> $envelope
     * @return array<string, mixed>
     */
    public function data(array $envelope): array
    {
        $data = $envelope['data'] ?? $envelope;

        return is_array($data) ? $data : [];
    }

    /**
     * @param array<string, mixed>|null $body
     * @param array<string, mixed> $query
     * @param array<string, string> $headers
     * @return array<string, mixed> Full response envelope.
     *
     * @throws ApiException On a 4xx/5xx response.
     * @throws TransportException When no response was received (outcome UNKNOWN).
     */
    public function request(
        string $method,
        string $path,
        ?array $body = null,
        array $query = [],
        array $headers = [],
    ): array {
        $method = strtoupper($method);
        $signedPath = PartnerSigner::normalizePath($path);
        $rawBody = $body === null ? '' : $this->encodeBody($body);

        // The signature covers the exact byte string we send — encode once, sign
        // that, send that. Re-encoding anywhere here would break verification.
        $requestHeaders = array_merge(
            $this->baseHeaders(),
            $headers,
            $this->signer->headers($method, $signedPath, $rawBody),
        );

        if ($rawBody !== '') {
            $requestHeaders['Content-Type'] = 'application/json';
        }

        $options = ['headers' => $requestHeaders];

        if ($query !== []) {
            $options['query'] = $query;
        }

        if ($rawBody !== '') {
            $options['body'] = $rawBody;
        }

        try {
            $response = $this->http->request($method, $this->baseUrl . '/' . $signedPath, $options);
        } catch (GuzzleException $e) {
            throw new TransportException(
                "Partner API {$method} /{$signedPath} did not return a response: " . $e->getMessage(),
                0,
                $e,
            );
        }

        $status = $response->getStatusCode();
        $decoded = json_decode((string) $response->getBody(), true);
        $payload = is_array($decoded) ? $decoded : [];

        if ($status >= 400) {
            throw new ApiException(
                is_string($payload['message'] ?? null)
                    ? $payload['message']
                    : "Partner API {$method} /{$signedPath} failed with status {$status}.",
                $status,
                $payload,
                $response->getHeaderLine('X-Correlation-Id') ?: null,
            );
        }

        return $payload;
    }

    /**
     * @param array<string, mixed> $body
     */
    private function encodeBody(array $body): string
    {
        return json_encode($body, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    /**
     * @return array<string, string>
     */
    private function baseHeaders(): array
    {
        $headers = [
            'Accept' => 'application/json',
            'X-Travelo-SDK-Name' => self::SDK_NAME,
            'X-Travelo-SDK-Version' => self::SDK_VERSION,
            'X-Request-Id' => bin2hex(random_bytes(8)),
        ];

        if ($this->defaultCurrency !== null) {
            $headers['X-Currency'] = $this->defaultCurrency;
        }

        if ($this->locale !== null) {
            $headers['Accept-Language'] = $this->locale;
        }

        if ($this->integrationName !== null) {
            $headers['X-Travelo-Integration-Name'] = $this->integrationName;
        }

        if ($this->integrationVersion !== null) {
            $headers['X-Travelo-Integration-Version'] = $this->integrationVersion;
        }

        return $headers;
    }
}
