<?php

declare(strict_types=1);

namespace TheOneDigi\TourSdk\Request;

use TheOneDigi\TourSdk\Request\Concerns\BuildsPayload;

class BookingListRequest implements RequestPayload
{
    use BuildsPayload;

    public function __construct(
        public readonly ?int $status = null,
        public readonly ?string $dateFrom = null,
        public readonly ?string $dateTo = null,
        public readonly ?string $query = null,
        public readonly ?int $page = null,
        public readonly ?int $perPage = null,
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function fromArray(array $payload): self
    {
        return new self(
            status: isset($payload['status']) ? (int) $payload['status'] : null,
            dateFrom: isset($payload['date_from']) ? (string) $payload['date_from'] : null,
            dateTo: isset($payload['date_to']) ? (string) $payload['date_to'] : null,
            query: isset($payload['q']) ? (string) $payload['q'] : null,
            page: isset($payload['page']) ? (int) $payload['page'] : null,
            perPage: isset($payload['per_page']) ? (int) $payload['per_page'] : null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->withoutNulls([
            'status' => $this->status,
            'date_from' => $this->dateFrom,
            'date_to' => $this->dateTo,
            'q' => $this->query,
            'page' => $this->page,
            'per_page' => $this->perPage,
        ]);
    }
}
