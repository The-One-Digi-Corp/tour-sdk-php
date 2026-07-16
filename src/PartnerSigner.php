<?php

declare(strict_types=1);

namespace TheOneDigi\TourSdk;

use TheOneDigi\TourSdk\Exception\ConfigurationException;

/**
 * Signs Partner API requests with the HMAC-SHA256 scheme travelo-api expects.
 *
 * string-to-sign = clientId \n timestamp \n METHOD \n path \n sha256hex(rawBody)
 *
 * The signed path must match Laravel's `$request->path()` on the server, i.e.
 * query string dropped and slashes trimmed ("api/partner/bookings").
 *
 * Conformance is pinned by tests/fixtures/partner-hmac-fixtures.json, shared
 * with the TypeScript SDK: both clients must produce identical signatures.
 */
final class PartnerSigner
{
    public const HEADER_CLIENT_ID = 'X-Partner-Client-Id';
    public const HEADER_TIMESTAMP = 'X-Partner-Timestamp';
    public const HEADER_SIGNATURE = 'X-Partner-Signature';

    /** travelo-api rejects requests outside this window (PartnerAuthService::MAX_SKEW_SECONDS). */
    public const MAX_SKEW_SECONDS = 300;

    public function __construct(
        private readonly string $clientId,
        private readonly string $secret,
    ) {
        if ($this->clientId === '') {
            throw new ConfigurationException('Partner clientId is required for HMAC signing.');
        }

        if ($this->secret === '') {
            throw new ConfigurationException('Partner secret is required for HMAC signing.');
        }
    }

    public static function normalizePath(string $path): string
    {
        return trim(explode('?', $path, 2)[0], '/');
    }

    public function stringToSign(string $method, string $path, string $rawBody, int|string $timestamp): string
    {
        return implode("\n", [
            $this->clientId,
            (string) $timestamp,
            strtoupper($method),
            self::normalizePath($path),
            hash('sha256', $rawBody),
        ]);
    }

    public function sign(string $method, string $path, string $rawBody, int|string $timestamp): string
    {
        return hash_hmac(
            'sha256',
            $this->stringToSign($method, $path, $rawBody, $timestamp),
            $this->secret,
        );
    }

    /**
     * @return array<string, string>
     */
    public function headers(string $method, string $path, string $rawBody, int|string|null $timestamp = null): array
    {
        $timestamp ??= time();

        return [
            self::HEADER_CLIENT_ID => $this->clientId,
            self::HEADER_TIMESTAMP => (string) $timestamp,
            self::HEADER_SIGNATURE => $this->sign($method, $path, $rawBody, $timestamp),
        ];
    }
}
