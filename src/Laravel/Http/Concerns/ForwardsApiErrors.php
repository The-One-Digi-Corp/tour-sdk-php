<?php

declare(strict_types=1);

namespace TheOneDigi\TourSdk\Laravel\Http\Concerns;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use TheOneDigi\TourSdk\Exception\ApiException;
use TheOneDigi\TourSdk\Exception\TransportException;

/**
 * Turns the SDK's two failure modes into responses, once, instead of in every
 * controller method.
 *
 * The distinction matters and is preserved: ApiException means travelo-api
 * answered and refused — its own body is forwarded so the caller sees the real
 * reason. TransportException means no answer came back at all, which is a 503 and
 * never the caller's fault.
 */
trait ForwardsApiErrors
{
    /**
     * @param callable(): array<string, mixed> $call
     * @param (callable(array<string, mixed>): array<string, mixed>)|null $transform
     *        Post-processes the decoded success payload before it becomes a
     *        response. Runs only on success — a forwarded error is never rewritten.
     */
    private function forward(callable $call, string $context = '', ?callable $transform = null): JsonResponse
    {
        try {
            $payload = $call();

            if ($transform !== null) {
                $payload = $transform($payload);
            }

            return response()->json($payload);
        } catch (ApiException $e) {
            return response()->json(
                $e->payload() !== [] ? $e->payload() : $this->envelope($e->status(), $e->getMessage()),
                $e->status(),
            );
        } catch (TransportException $e) {
            Log::error('tour-sdk controller mode: travelo unreachable', [
                'context' => $context,
                'method' => $e->method,
                'path' => $e->path,
                'error' => $e->getMessage(),
            ]);

            return response()->json(
                $this->envelope(503, 'Tour service is unavailable.', ['reason' => 'travelo_unreachable']),
                503,
            );
        }
    }

    /**
     * travelo-api's envelope, matched exactly — callers must not be able to tell
     * an SDK-generated error from an upstream one.
     *
     * @param array<string, mixed> $errors
     * @return array<string, mixed>
     */
    private function envelope(int $status, string $message, array $errors = [], mixed $data = null): array
    {
        return [
            'status' => $status,
            'message' => $message,
            'errors' => $errors,
            'data' => $data,
        ];
    }
}
