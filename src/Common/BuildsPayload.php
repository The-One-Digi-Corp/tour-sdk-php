<?php

declare(strict_types=1);

namespace TheOneDigi\TourSdk\Common;

trait BuildsPayload
{
    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    private function withoutNulls(array $payload): array
    {
        // A null the caller actually passed is a null they meant: the Partner API
        // clears a field by receiving it as null, so dropping every null would make
        // "erase this applicant's date of birth" unexpressible. Nulls that are just
        // unset optional arguments still go.
        //
        // fromArray() records which keys were present; a request built with named
        // arguments has no such record and keeps the plain drop-all-nulls rule.
        $provided = array_flip($this->providedKeys ?? []);

        return array_filter(
            $payload,
            static fn (mixed $value, string $key): bool => $value !== null || array_key_exists($key, $provided),
            ARRAY_FILTER_USE_BOTH,
        );
    }

    /**
     * @param class-string<RequestPayload> $class
     * @return list<RequestPayload>
     */
    private static function payloadListFromArray(mixed $items, string $class): array
    {
        if (! is_array($items)) {
            return [];
        }

        $payloads = [];

        foreach ($items as $item) {
            if ($item instanceof RequestPayload) {
                $payloads[] = $item;
                continue;
            }

            if (is_array($item) && is_callable([$class, 'fromArray'])) {
                $payload = $class::fromArray($item);

                if ($payload instanceof RequestPayload) {
                    $payloads[] = $payload;
                }
            }
        }

        return $payloads;
    }

    /**
     * @param class-string<RequestPayload> $class
     * @return list<array<string, mixed>>
     */
    private function payloadListToArray(mixed $items, string $class): array
    {
        if (! is_array($items)) {
            return [];
        }

        $payloads = [];

        foreach ($items as $item) {
            if ($item instanceof RequestPayload) {
                $payloads[] = $item->toArray();
                continue;
            }

            if (is_array($item) && is_callable([$class, 'fromArray'])) {
                $payload = $class::fromArray($item);

                if ($payload instanceof RequestPayload) {
                    $payloads[] = $payload->toArray();
                }
            }
        }

        return $payloads;
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * Hook for the MANUAL BODY region of a generated request; a no-op by default.
     *
     * fromArray() runs the incoming payload through this before mapping it onto
     * constructor arguments, which is where a request folds travelo-api's
     * backward-compatible aliases onto one canonical key. A class redefining this
     * takes precedence over the trait's copy.
     *
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    protected static function normalizeManual(array $payload): array
    {
        return $payload;
    }

    /**
     * Hook for the MANUAL BODY region of a generated request; a no-op by default.
     *
     * Called at the end of the constructor, so a request can reject a payload the
     * API would reject anyway — locally, with a usable message, instead of a 422
     * after a round trip. The OpenAPI contract carries no cross-field rules
     * ("infants may not outnumber adults"), so they can only live in MANUAL BODY.
     */
    protected function validateManual(): void
    {
    }
}
