<?php

declare(strict_types=1);

namespace TheOneDigi\TourSdk\Request;

use TheOneDigi\TourSdk\Request\Concerns\BuildsPayload;

class TourTakeRequest implements RequestPayload
{
    use BuildsPayload;

    public function __construct(public readonly ?int $take = null)
    {
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function fromArray(array $payload): self
    {
        return new self(isset($payload['take']) ? (int) $payload['take'] : null);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->withoutNulls(['take' => $this->take]);
    }
}
