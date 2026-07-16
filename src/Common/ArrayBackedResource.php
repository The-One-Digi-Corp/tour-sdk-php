<?php

declare(strict_types=1);

namespace TheOneDigi\TourSdk\Common;

use JsonSerializable;

/**
 * Base class for SDK resources.
 *
 * Resources expose the common, documented fields as typed public properties and
 * keep the original payload available through toArray() for additive Partner API
 * fields that older SDK versions do not know about yet.
 */
abstract class ArrayBackedResource implements JsonSerializable
{
    /**
     * @param array<string, mixed> $attributes
     */
    public function __construct(protected readonly array $attributes)
    {
    }

    /**
     * @param array<string, mixed> $attributes
     */
    public static function fromArray(array $attributes): static
    {
        return new static($attributes);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->attributes;
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->attributes[$key] ?? $default;
    }

    /**
     * Hook for the MANUAL HYDRATION region of a generated resource.
     *
     * A no-op here so a generated class can always call it, whether or not anyone
     * has filled in its manual region. Overrides assign readonly properties
     * declared in the same class, which PHP allows from that class's own scope.
     */
    protected function hydrateManual(): void
    {
    }

    protected function scalar(string $key): int|string|null
    {
        $value = $this->attributes[$key] ?? null;

        if (is_int($value) || is_string($value)) {
            return $value;
        }

        if (is_float($value)) {
            return (string) $value;
        }

        return null;
    }

    protected function string(string $key, string $default = ''): string
    {
        $value = $this->attributes[$key] ?? null;

        return is_scalar($value) ? (string) $value : $default;
    }

    protected function nullableString(string $key): ?string
    {
        $value = $this->attributes[$key] ?? null;

        return is_scalar($value) ? (string) $value : null;
    }

    protected function int(string $key, int $default = 0): int
    {
        $value = $this->attributes[$key] ?? null;

        return is_numeric($value) ? (int) $value : $default;
    }

    protected function nullableInt(string $key): ?int
    {
        $value = $this->attributes[$key] ?? null;

        return is_numeric($value) ? (int) $value : null;
    }

    protected function float(string $key, float $default = 0.0): float
    {
        $value = $this->attributes[$key] ?? null;

        return is_numeric($value) ? (float) $value : $default;
    }

    protected function bool(string $key, bool $default = false): bool
    {
        $value = $this->attributes[$key] ?? null;

        return is_bool($value) ? $value : (is_numeric($value) ? (bool) $value : $default);
    }

    /**
     * @return array<string, mixed>
     */
    protected function array(string $key): array
    {
        $value = $this->attributes[$key] ?? [];

        return is_array($value) ? $value : [];
    }

    /**
     * @param array<mixed> $items
     * @param class-string<TResource> $resourceClass
     * @return list<TResource>
     *
     * @template TResource of ArrayBackedResource
     */
    protected static function resourceList(array $items, string $resourceClass): array
    {
        $resources = [];

        foreach ($items as $item) {
            if (is_array($item)) {
                $resources[] = $resourceClass::fromArray($item);
            }
        }

        return $resources;
    }
}
