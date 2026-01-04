<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ChannexRateLimiter
{
    private const GLOBAL_LIMIT = 20; // Total ARI requests per minute
    private const PROPERTY_LIMIT = 10; // Per property per minute
    private const WINDOW_MINUTES = 1;
    private const MAX_PAYLOAD_SIZE = 10 * 1024 * 1024; // 10MB in bytes

    /**
     * Check if we can make a request for a specific property
     */
    public function canMakeRequest(string $propertyId = null, string $endpoint = 'global'): bool
    {
        $now = Carbon::now();
        $windowStart = $now->copy()->subMinutes(self::WINDOW_MINUTES);

        // Check global limit
        $globalKey = "channex_rate_limit:global:{$now->format('Y-m-d-H-i')}";
        $globalCount = Cache::get($globalKey, 0);

        if ($globalCount >= self::GLOBAL_LIMIT) {
            Log::warning("Channex global rate limit exceeded", [
                'current' => $globalCount,
                'limit' => self::GLOBAL_LIMIT,
                'window' => $now->format('Y-m-d H:i'),
            ]);
            return false;
        }

        // Check property-specific limit if property ID is provided
        if ($propertyId) {
            $propertyKey = "channex_rate_limit:property:{$propertyId}:{$now->format('Y-m-d-H-i')}";
            $propertyCount = Cache::get($propertyKey, 0);

            if ($propertyCount >= self::PROPERTY_LIMIT) {
                Log::warning("Channex property rate limit exceeded", [
                    'property_id' => $propertyId,
                    'current' => $propertyCount,
                    'limit' => self::PROPERTY_LIMIT,
                    'window' => $now->format('Y-m-d H:i'),
                ]);
                return false;
            }
        }

        return true;
    }

    /**
     * Record a request being made
     */
    public function recordRequest(string $propertyId = null): void
    {
        $now = Carbon::now();
        $windowStart = $now->copy()->subMinutes(self::WINDOW_MINUTES);

        // Increment global counter
        $globalKey = "channex_rate_limit:global:{$now->format('Y-m-d-H-i')}";
        Cache::increment($globalKey);
        Cache::put($globalKey, Cache::get($globalKey), Carbon::now()->addMinutes(2));

        // Increment property counter if property ID is provided
        if ($propertyId) {
            $propertyKey = "channex_rate_limit:property:{$propertyId}:{$now->format('Y-m-d-H-i')}";
            Cache::increment($propertyKey);
            Cache::put($propertyKey, Cache::get($propertyKey), Carbon::now()->addMinutes(2));
        }
    }

    /**
     * Wait until we can make a request
     */
    public function waitForAvailability(string $propertyId = null, int $maxWaitSeconds = 60): bool
    {
        $startTime = time();

        while (time() - $startTime < $maxWaitSeconds) {
            if ($this->canMakeRequest($propertyId)) {
                return true;
            }

            // Wait 1 second before checking again
            sleep(1);
        }

        Log::error("Channex rate limit wait timeout", [
            'property_id' => $propertyId,
            'max_wait_seconds' => $maxWaitSeconds,
        ]);

        return false;
    }

    /**
     * Check payload size
     */
    public function isPayloadSizeValid(array $payload): bool
    {
        $payloadSize = strlen(json_encode($payload));
        return $payloadSize <= self::MAX_PAYLOAD_SIZE;
    }

    /**
     * Get current usage statistics
     */
    public function getUsageStats(string $propertyId = null): array
    {
        $now = Carbon::now();

        $globalKey = "channex_rate_limit:global:{$now->format('Y-m-d-H-i')}";
        $globalCount = Cache::get($globalKey, 0);

        $stats = [
            'global' => [
                'current' => $globalCount,
                'limit' => self::GLOBAL_LIMIT,
                'remaining' => max(0, self::GLOBAL_LIMIT - $globalCount),
            ],
        ];

        if ($propertyId) {
            $propertyKey = "channex_rate_limit:property:{$propertyId}:{$now->format('Y-m-d-H-i')}";
            $propertyCount = Cache::get($propertyKey, 0);

            $stats['property'] = [
                'property_id' => $propertyId,
                'current' => $propertyCount,
                'limit' => self::PROPERTY_LIMIT,
                'remaining' => max(0, self::PROPERTY_LIMIT - $propertyCount),
            ];
        }

        return $stats;
    }

    /**
     * Split large payload into smaller chunks
     */
    public function splitPayload(array $payload, int $maxItemsPerChunk = 100): array
    {
        $values = $payload['values'] ?? $payload;

        if (count($values) <= $maxItemsPerChunk) {
            return [$payload];
        }

        $chunks = [];
        $chunkedValues = array_chunk($values, $maxItemsPerChunk);

        foreach ($chunkedValues as $chunk) {
            $chunks[] = ['values' => $chunk];
        }

        return $chunks;
    }

    /**
     * Reset property counters when we get a rate limit response
     */
    public function resetPropertyCounters(string $propertyId): void
    {
        $now = Carbon::now();
        $propertyKey = "channex_rate_limit:property:{$propertyId}:{$now->format('Y-m-d-H-i')}";

        Cache::forget($propertyKey);

        Log::info("Reset rate limit counters for property", [
            'property_id' => $propertyId,
            'window' => $now->format('Y-m-d H:i'),
        ]);
    }

    /**
     * Get retry after time from error response
     */
    public function parseRetryAfterTime(array $errorResponse, $response = null): int
    {
        // Check response body first
        if (isset($errorResponse['errors']['details']['retry_after'])) {
            return $errorResponse['errors']['details']['retry_after'];
        }

        // Check Retry-After header if response object is provided
        if ($response && method_exists($response, 'header')) {
            $retryAfterHeader = $response->header('Retry-After');
            if ($retryAfterHeader) {
                return (int) $retryAfterHeader;
            }
        }

        // Default fallback
        return 60;
    }

    /**
     * Check if response is a rate limit error
     */
    public function isRateLimitError(int $statusCode, array $response = []): bool
    {
        if ($statusCode !== 429) {
            return false;
        }

        return isset($response['errors']['code']) &&
            $response['errors']['code'] === 'too_many_requests';
    }
}
