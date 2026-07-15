<?php

declare(strict_types=1);

namespace TheOneDigi\TourSdk\Request;

use InvalidArgumentException;
use TheOneDigi\TourSdk\Request\Concerns\BuildsPayload;

class TourCalendarDateRequest implements RequestPayload
{
    use BuildsPayload;

    public function __construct(
        public readonly string $date,
        public readonly ?int $pax = null,
    ) {
        if ($this->date === '') {
            throw new InvalidArgumentException('date is required.');
        }

        if ($this->pax !== null && $this->pax < 1) {
            throw new InvalidArgumentException('pax must be at least 1.');
        }
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function fromArray(array $payload): self
    {
        return new self(
            date: (string) ($payload['date'] ?? ''),
            pax: isset($payload['pax']) ? (int) $payload['pax'] : null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->withoutNulls([
            'date' => $this->date,
            'pax' => $this->pax,
        ]);
    }
}
