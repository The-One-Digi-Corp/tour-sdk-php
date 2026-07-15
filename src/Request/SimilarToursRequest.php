<?php

declare(strict_types=1);

namespace TheOneDigi\TourSdk\Request;

use InvalidArgumentException;
use TheOneDigi\TourSdk\Request\Concerns\BuildsPayload;

class SimilarToursRequest implements RequestPayload
{
    use BuildsPayload;

    public function __construct(
        public readonly ?string $tourCode = null,
        public readonly ?int $tourId = null,
        public readonly ?int $take = null,
    ) {
        if (($this->tourCode === null || $this->tourCode === '') && $this->tourId === null) {
            throw new InvalidArgumentException('Either tourCode or tourId is required.');
        }
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function fromArray(array $payload): self
    {
        return new self(
            tourCode: isset($payload['tour_code']) ? (string) $payload['tour_code'] : null,
            tourId: isset($payload['tour_id']) ? (int) $payload['tour_id'] : null,
            take: isset($payload['take']) ? (int) $payload['take'] : null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->withoutNulls([
            'tour_code' => $this->tourCode,
            'tour_id' => $this->tourId,
            'take' => $this->take,
        ]);
    }
}
