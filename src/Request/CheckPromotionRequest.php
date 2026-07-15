<?php

declare(strict_types=1);

namespace TheOneDigi\TourSdk\Request;

use InvalidArgumentException;
use TheOneDigi\TourSdk\Request\Concerns\BuildsPayload;

class CheckPromotionRequest implements RequestPayload
{
    use BuildsPayload;

    public function __construct(public readonly string $code)
    {
        if ($this->code === '') {
            throw new InvalidArgumentException('Promotion code is required.');
        }
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function fromArray(array $payload): self
    {
        return new self((string) ($payload['code'] ?? ''));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return ['code' => $this->code];
    }
}
