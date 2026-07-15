<?php

declare(strict_types=1);

namespace TheOneDigi\TourSdk\Request\Concerns;

trait BuildsPayload
{
    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    private function withoutNulls(array $payload): array
    {
        return array_filter($payload, static fn (mixed $value): bool => $value !== null);
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
