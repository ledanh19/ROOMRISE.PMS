<?php

namespace App\Services;

use App\Helpers\Helper;
use App\Models\Booking;
use App\Models\BookingCustomer;
use App\Models\BookingRoom;
use App\Models\Customer;
use App\Models\Property;
use App\Models\RatePlan;
use App\Models\RatePlanOTA;
use App\Models\Room;
use App\Models\RoomUnit;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ChannexService
{
    protected $baseUrl;
    protected $apiKey;
    protected $rateLimiter;

    public function __construct()
    {
        $this->baseUrl = config('services.channex.base_url');
        $this->apiKey = config('services.channex.api_key');
        $this->rateLimiter = app(ChannexRateLimiter::class);
    }

    public function getProperties()
    {
        logger($this->apiKey);
        $response = Http::withHeaders([
            'user-api-key' => $this->apiKey,
        ])->get("{$this->baseUrl}/properties");

        logger($response);

        if ($response->successful()) {
            return $response->json('data');
        }

        throw new \Exception('Failed to fetch properties from Channex');
    }

    public function getAllProperties(int $pageSize = 100): array
    {
        $all = [];
        $page = 1;

        do {
            $response = Http::timeout(30)->withHeaders([
                'user-api-key' => $this->apiKey,
            ])->get("{$this->baseUrl}/properties", [
                'pagination[page]'  => $page,
                'pagination[limit]' => $pageSize,
            ]);

            if (!$response->successful()) {
                throw new \Exception("Channex API error: " . $response->body());
            }

            $data = $response->json('data', []);
            $all  = array_merge($all, $data);

            $meta = $response->json('meta', []);
            $hasNext = isset($meta['total'])
                && ($page * $meta['limit']) < $meta['total'];

            $page++;
        } while ($hasNext);

        return $all;
    }

    public function getAllRoomTypes(int $pageSize = 100): array
    {
        $all = [];
        $page = 1;

        do {
            $response = Http::withHeaders([
                'user-api-key' => $this->apiKey,
            ])->get("{$this->baseUrl}/room_types", [
                'pagination[page]'  => $page,
                'pagination[limit]' => $pageSize,
            ]);

            if (!$response->successful()) {
                throw new \Exception("Channex API error: " . $response->body());
            }

            $data = $response->json('data', []);
            $all  = array_merge($all, $data);

            $meta = $response->json('meta', []);
            $hasNext = isset($meta['total'])
                && ($page * $meta['limit']) < $meta['total'];

            $page++;
        } while ($hasNext);

        return $all;
    }

    public function getAllRatePlans(int $pageSize = 100): array
    {
        $all = [];
        $page = 1;

        do {
            $response = Http::withHeaders([
                'user-api-key' => $this->apiKey,
            ])->get("{$this->baseUrl}/rate_plans", [
                'pagination[page]'  => $page,
                'pagination[limit]' => $pageSize,
            ]);

            if (!$response->successful()) {
                throw new \Exception("Channex API error: " . $response->body());
            }

            $data = $response->json('data', []);
            $all  = array_merge($all, $data);

            $meta = $response->json('meta', []);
            $hasNext = isset($meta['total'])
                && ($page * $meta['limit']) < $meta['total'];

            $page++;
        } while ($hasNext);

        return $all;
    }

    public function createProperty(array $property): ?string
    {
        $payload = [
            'property' => [
                'title'    => $property['name'] ?? '',
                'property_type' => $property['type'],
                'currency' => $property['currency'] ?? 'USD',
                'email'    => $property['email'] ?? null,
                'phone'    => $property['phone'] ?? null,
                'country'  => $property['country'] ?? '',
                'city'     => $property['city'] ?? '',
                'address'  => $property['address'] ?? '',
            ],
        ];

        $response = Http::withHeaders([
            'user-api-key' => $this->apiKey,
        ])->post("{$this->baseUrl}/properties", $payload);

        if ($response->successful()) {
            Log::info('Create property on channex successfully!');
            return $response->json('data.id');
        }

        Log::error('Failed to create Channex property', [
            'request_data' => $payload,
            'response'     => $response->body(),
        ]);
        throw new \Exception("Có lỗi xảy ra khi đồng bộ với channex");
    }

    public function updateProperty(string $externalId, array $propertyData): void
    {
        $payload = [
            'property' => [
                'title'    => $propertyData['name'],
                'property_type' => $propertyData['type'],
                'currency' => $propertyData['currency'] ?? 'USD',
                'email'    => $propertyData['email'] ?? '',
                'phone'    => $propertyData['phone'] ?? '',
                'country'  => $propertyData['country'] ?? '',
                'city'     => $propertyData['city'] ?? '',
                'address'  => $propertyData['address'] ?? '',
            ],
        ];

        $response = Http::withHeaders([
            'user-api-key' => $this->apiKey,
        ])->put("{$this->baseUrl}/properties/{$externalId}", $payload);
        if (!$response->successful()) {
            throw new \Exception("Channex update property failed: " . $response->body());
        }
    }

    public function deleteProperty(string $externalId): void
    {
        $response = Http::withHeaders([
            'user-api-key' => $this->apiKey,
        ])->delete("{$this->baseUrl}/properties/{$externalId}");

        if (!$response->successful()) {
            throw new \Exception("Channex delete property failed: " . $response->body());
        }
    }

    public function createRoomType(array $data): string
    {
        $response = Http::withHeaders([
            'user-api-key' => $this->apiKey,
        ])->post("{$this->baseUrl}/room_types", [
            'room_type' => $data,
        ]);

        if (!$response->successful()) {
            throw new \Exception("Channex create room_type failed: " . $response->body());
        }

        return $response->json('data.id');
    }

    public function updateRoomType(string $id, array $data): void
    {
        $response = Http::withHeaders([
            'user-api-key' => $this->apiKey,
        ])->put("{$this->baseUrl}/room_types/{$id}", [
            'room_type' => $data,
        ]);

        if (!$response->successful()) {
            throw new \Exception("Channex update room_type failed: " . $response->body());
        }
    }

    public function deleteRoomType(string $id): void
    {
        $response = Http::withHeaders([
            'user-api-key' => $this->apiKey,
        ])->delete("{$this->baseUrl}/room_types/{$id}");

        if (!$response->successful()) {
            throw new \Exception("Channex delete room_type failed: " . $response->body());
        }
    }

    public function createRatePlan(array $data): array
    {
        $response = Http::withHeaders([
            'user-api-key' => $this->apiKey,
        ])->post("{$this->baseUrl}/rate_plans", [
            'rate_plan' => $data,
        ]);

        if (!$response->successful()) {
            throw new \Exception("Channex create rate_plan failed: " . $response->body());
        }

        return $response->json('data');
    }

    public function updateRatePlan(string $id, array $data): array
    {
        $response = Http::withHeaders([
            'user-api-key' => $this->apiKey,
        ])->put("{$this->baseUrl}/rate_plans/{$id}", [
            'rate_plan' => $data,
        ]);

        if (!$response->successful()) {
            throw new \Exception("Channex update rate_plan failed: " . $response->body());
        }

        return $response->json('data');
    }

    public function deleteRatePlan(string $id): void
    {
        $response = Http::withHeaders([
            'user-api-key' => $this->apiKey,
        ])->delete("{$this->baseUrl}/rate_plans/{$id}");

        if (!$response->successful()) {
            throw new \Exception("Channex delete rate_plan failed: " . $response->body());
        }
    }

    // public function recalculateAndSyncAvailabilityForRoom(Room $room, string $startDate, string $endDate): void
    // {
    //     $propertyId = $room->property->external_id;
    //     $roomTypeId = $room->external_id;

    //     if (!$propertyId || !$roomTypeId) {
    //         throw new \Exception('Missing Channex property or room external_id');
    //     }

    //     $totalUnits = $room->roomUnits()->count();

    //     $period = CarbonPeriod::create($startDate, $endDate);

    //     $values = [];

    //     foreach ($period as $date) {
    //         $dateStr = $date->toDateString();
    //         // Count bookings that overlap with this specific date
    //         // A booking overlaps if: check_in_date <= current_date < check_out_date
    //         $bookedCount = Booking::where('room_id', $room->id)
    //             ->where(function ($query) use ($dateStr) {
    //                 $query->where(function ($q) use ($dateStr) {
    //                     $q->where('check_in_date', '<=', $dateStr)
    //                         ->where('check_out_date', '>', $dateStr);
    //                 })->orWhere(function ($q) use ($dateStr) {
    //                     // Handle same-day bookings? // todo: double check
    //                     $q->where('check_in_date', '=', $dateStr)
    //                         ->where('check_out_date', '=', $dateStr);
    //                 });
    //             })
    //             ->whereNotIn('status', ['Hủy', 'Bị Hủy'])
    //             ->count();


    //         $available = max(0, $totalUnits - $bookedCount);

    //         $values[] = [
    //             'property_id'   => $propertyId,
    //             'room_type_id'  => $roomTypeId,
    //             'date'          => $dateStr,
    //             'availability'  => $available,
    //         ];
    //     }

    //     if (!empty($values)) {
    //         $this->updateAvailability($values);
    //     }
    // }

    public function recalculateAndSyncAvailabilityForRoom(Room $room, string $startDate, string $endDate): void
    {
        $propertyId = $room->property->external_id;
        $roomTypeId = $room->external_id;

        if (!$propertyId || !$roomTypeId) {
            throw new \Exception('Missing Channex property or room external_id');
        }

        $period = CarbonPeriod::create($startDate, $endDate);
        $values = [];

        foreach ($period as $date) {
            $dateStr = $date->toDateString();

            // Lấy availability từ inventory table
            $inventory = \App\Models\Inventory::where([
                'property_id' => $room->property->id,
                'room_type_id' => $room->id,
                'date' => $dateStr,
                'rate_plan_id' => null
            ])->first();

            // Nếu có inventory record, sử dụng giá trị đó
            // Nếu không có, tính toán từ số phòng tổng
            if ($inventory) {
                $available = $inventory->availability;
            } else {
                $totalUnits = $room->roomUnits()->count();
                $available = $totalUnits;
            }

            $values[] = [
                'property_id'   => $propertyId,
                'room_type_id'  => $roomTypeId,
                'date'          => $dateStr,
                'availability'  => $available,
            ];
        }

        if (!empty($values)) {
            $this->updateAvailability($values);
        }
    }


    public function getBookingRevisionDetail($id)
    {
        $response = Http::withHeaders([
            'user-api-key' => $this->apiKey,
        ])->get("{$this->baseUrl}/booking_revisions/{$id}");


        if ($response->successful()) {
            return $response->json('data');
        }

        throw new \Exception('Failed to fetch booking revision');
    }

    // Webhook
    public function createWebhook($channexPropertyId): string
    {
        $callBackBaseUrl = config('services.channex.callback_url') ?: url()->to('/');

        $data = [
            "property_id" => $channexPropertyId,
            "callback_url" => $callBackBaseUrl . "/api/v1/webhook/channex",
            "event_mask" => "*",
            "is_active" => true,
            "send_data" => true
        ];

        $response = Http::withHeaders([
            'user-api-key' => $this->apiKey,
        ])->post("{$this->baseUrl}/webhooks", [
            'webhook' => $data,
        ]);

        if (!$response->successful()) {
            Log::error($response);
            throw new \Exception("Có lỗi xảy ra khi đồng bộ với channex");
        }

        return $response->json('data.id');
    }

    public function updateWebhook(Property $property, $status): string
    {
        $callBackBaseUrl = config('services.channex.callback_url') ?: url()->current();

        $data = [
            "property_id" => $property->external_id,
            "callback_url" => $callBackBaseUrl . "/api/v1/webhook/channex",
            "event_mask" => "*",
            "is_active" => $status,
            "send_data" => true
        ];

        $response = Http::withHeaders([
            'user-api-key' => $this->apiKey,
        ])->put("{$this->baseUrl}/webhooks/{$property->webhook_id}", [
            'webhook' => $data,
        ]);

        if (!$response->successful()) {
            Log::error($response);
            throw new \Exception("Có lỗi xảy ra khi đồng bộ với channex");
        }

        return $response->json('data.id');
    }

    public function getBookingRevisionsFeed(int $pageSize = 100): array
    {
        $all = [];
        $page = 1;

        do {
            $response = Http::timeout(30)->withHeaders([
                'user-api-key' => $this->apiKey,
            ])->get("{$this->baseUrl}/booking_revisions/feed", [
                'pagination[page]'  => $page,
                'pagination[limit]' => $pageSize,
            ]);

            if (!$response->successful()) {
                throw new \Exception("Channex API error: " . $response->body());
            }

            $data = $response->json('data', []);
            $all  = array_merge($all, $data);

            $meta = $response->json('meta', []);
            $hasNext = isset($meta['total'])
                && ($page * $meta['limit']) < $meta['total'];

            $page++;
        } while ($hasNext);

        return $all;
    }

    public function getFutureBookingsByProperty($propertyId = null): array
    {
        $all = [];
        $page = 1;

        do {
            $response = Http::timeout(30)->withHeaders([
                'user-api-key' => $this->apiKey,
            ])->get("{$this->baseUrl}/bookings", [
                'pagination[page]'  => $page,
                'pagination[limit]' => 100,
                'order[inserted_at]' => 'asc',
                'filter[acknowledge_status]' => 'pending',
                'filter[property_id]' => $propertyId,
                'filter[arrival_date][gte]' => now()->toDateString()
            ]);

            if (!$response->successful()) {
                throw new \Exception("Channex API error: " . $response->body());
            }

            logger($response->json('meta'));

            $data = $response->json('data', []);
            // Filter bookings with acknowledge_status = 'pending'
            // $data = array_filter($data, function ($booking) {
            //     return isset($booking['attributes']['acknowledge_status']) && $booking['attributes']['acknowledge_status'] === 'pending';
            // });
            $all  = array_merge($all, $data);

            $meta = $response->json('meta', []);
            $hasNext = isset($meta['total'])
                && ($page * $meta['limit']) < $meta['total'];

            $page++;
        } while ($hasNext);

        return $all;
    }

    public function acknowledgeBookingRevisions(array $revisionIds): void
    {
        Http::withHeaders([
            'user-api-key' => $this->apiKey,
        ])->post("{$this->baseUrl}/booking_revisions_feed/acknowledge", [
            'ids' => $revisionIds,
        ]);
    }

    /**
     * Acknowledge a single booking revision by its ID using the new Channex endpoint.
     *
     * @param string $revisionId
     * @return void
     * @throws \Exception
     */
    public function acknowledgeBookingRevision(string $revisionId): void
    {
        $response = Http::withHeaders([
            'user-api-key' => $this->apiKey,
        ])->post("{$this->baseUrl}/booking_revisions/{$revisionId}/ack");

        if (!$response->successful()) {
            Log::error('Channex: Failed to acknowledge booking revision', [
                'revision_id' => $revisionId,
                'response' => $response->body(),
            ]);
            // throw new \Exception('Channex: Failed to acknowledge booking revision');
        }
    }

    public function getAvailability(string $propertyId, string $startDate, string $endDate)
    {
        $response = Http::timeout(60)->withHeaders([
            'user-api-key' => $this->apiKey,
        ])->get("{$this->baseUrl}/availability", [
            'filter[property_id]' => $propertyId,
            'filter[date][gte]' => $startDate,
            'filter[date][lte]' => $endDate,
        ]);

        if (!$response->successful()) {
            Log::error('Channex: Failed to get availability', [
                'property_id' => $propertyId,
                'response' => $response->body(),
            ]);
            throw new \Exception('Channex: Failed to get availability');
        }

        return $response->json('data');
    }

    public function getRates(string $propertyId, string $startDate, string $endDate)
    {
        $response = Http::timeout(60)->withHeaders([
            'user-api-key' => $this->apiKey,
        ])->get("{$this->baseUrl}/restrictions", [
            'filter[property_id]' => $propertyId,
            'filter[date][gte]' => $startDate,
            'filter[date][lte]' => $endDate,
            'filter[restrictions]' => 'rate',
        ]);

        if (!$response->successful()) {
            Log::error('Channex: Failed to get rates', [
                'property_id' => $propertyId,
                'response' => $response->body(),
            ]);
            throw new \Exception('Channex: Failed to get rates');
        }

        return $response->json('data');
    }

    public function updateAvailability(array $updates)
    {
        // Extract property ID from first update for rate limiting
        $propertyId = $updates[0]['property_id'] ?? null;
        $payload = ['values' => Helper::optimizeAvailabilityPayload($updates)];
        // Check payload size
        if (!$this->rateLimiter->isPayloadSizeValid($payload)) {
            $chunks = $this->rateLimiter->splitPayload($payload, 50);
            return $this->processChunkedRequests($chunks, 'updateAvailability', $propertyId);
        }
        return $this->makeRateLimitedRequest('availability', $payload, $propertyId);
    }

    public function updateRestrictions(array $updates)
    {
        // Extract property ID from first update for rate limiting
        $propertyId = $updates[0]['property_id'] ?? null;
        $payload = ['values' => Helper::optimizeOtaUpdates($updates)];
        // Check payload size
        if (!$this->rateLimiter->isPayloadSizeValid($payload)) {
            $chunks = $this->rateLimiter->splitPayload($payload, 50);
            return $this->processChunkedRequests($chunks, 'updateRestrictions', $propertyId);
        }
        return $this->makeRateLimitedRequest('restrictions', $payload, $propertyId);
    }

    /**
     * Make a rate-limited request to Channex with proper error handling
     */
    private function makeRateLimitedRequest(string $endpoint, array $payload, string $propertyId = null, int $retryCount = 0)
    {
        $maxRetries = 3;

        try {
            // Wait for rate limit availability
            if (!$this->rateLimiter->waitForAvailability($propertyId)) {
                throw new \Exception("Rate limit exceeded for {$endpoint} endpoint");
            }

            // Record the request
            $this->rateLimiter->recordRequest($propertyId);

            // dd("{$this->baseUrl}/{$endpoint}");
            // dd($payload);
            // Make the actual request
            $response = Http::timeout(60)->withHeaders([
                'user-api-key' => $this->apiKey,
            ])->post("{$this->baseUrl}/{$endpoint}", $payload);

            if ($response->successful()) {
                $responseData = $response->json();
                // Log task id if present in data
                if (
                    isset($responseData['data'][0]['id']) &&
                    isset($responseData['data'][0]['type']) &&
                    $responseData['data'][0]['type'] === 'task'
                ) {
                    $taskId = $responseData['data'][0]['id'];
                    logger("Update {$endpoint} thành công, task id: {$taskId}");
                    logger($response->json());
                } else {
                    logger("Update {$endpoint} thành công");
                    logger($response->json());
                }
                return $responseData;
            }

            // Handle rate limit error specifically
            if ($response->status() === 429) {
                $errorData = $response->json();

                // Check for retry_after in response body first
                $retryAfter = null;
                $errorId = null;

                if (isset($errorData['errors']['details']['retry_after'])) {
                    $retryAfter = $errorData['errors']['details']['retry_after'];
                    $errorId = $errorData['errors']['details']['id'] ?? 'unknown';
                }

                // If not in body, check Retry-After header
                if ($retryAfter === null) {
                    $retryAfterHeader = $response->header('Retry-After');
                    if ($retryAfterHeader) {
                        $retryAfter = (int) $retryAfterHeader;
                        $errorId = 'header_retry_after';
                    }
                }

                // If still no retry_after, use default
                if ($retryAfter === null) {
                    $retryAfter = 60; // Default 60 seconds
                    $errorId = 'default_retry_after';
                }

                Log::warning("Channex rate limit hit", [
                    'endpoint' => $endpoint,
                    'property_id' => $propertyId,
                    'retry_after' => $retryAfter,
                    'error_id' => $errorId,
                    'retry_count' => $retryCount,
                    'retry_after_source' => $errorId === 'header_retry_after' ? 'header' : ($errorId === 'default_retry_after' ? 'default' : 'body'),
                    'response_headers' => $this->getRelevantHeaders($response),
                    'response_body' => $errorData,
                ]);

                // If we haven't exceeded max retries, wait and retry
                if ($retryCount < $maxRetries) {
                    Log::info("Waiting {$retryAfter} seconds before retry");
                    sleep($retryAfter + 1); // Add 1 second buffer

                    // Reset rate limiter counters for this property
                    $this->rateLimiter->resetPropertyCounters($propertyId);

                    return $this->makeRateLimitedRequest($endpoint, $payload, $propertyId, $retryCount + 1);
                } else {
                    throw new \Exception("Rate limit exceeded after {$maxRetries} retries. Retry after: {$retryAfter} seconds");
                }
            }

            // Handle other errors
            Log::error("Channex API request failed", [
                'endpoint' => $endpoint,
                'status' => $response->status(),
                'payload_size' => strlen(json_encode($payload)),
                'response' => $response->body(),
            ]);

            throw new \Exception("Channex API request failed: " . $response->body());
        } catch (\Exception $e) {
            logger($e);
            // If it's not a rate limit error, re-throw
            if (!str_contains($e->getMessage(), 'rate limit')) {
                throw $e;
            }

            // For rate limit errors, log and re-throw
            Log::error("Rate limit error in Channex API", [
                'endpoint' => $endpoint,
                'property_id' => $propertyId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Process chunked requests with rate limiting and retry logic
     */
    private function processChunkedRequests(array $chunks, string $method, string $propertyId = null)
    {
        $results = [];

        foreach ($chunks as $index => $chunk) {
            Log::info("Processing chunk {$index} of " . count($chunks), [
                'chunk_size' => count($chunk['values'] ?? $chunk),
                'property_id' => $propertyId,
            ]);

            try {
                if ($method === 'updateAvailability') {
                    $result = $this->makeRateLimitedRequest('availability', $chunk, $propertyId);
                } else {
                    $result = $this->makeRateLimitedRequest('restrictions', $chunk, $propertyId);
                }

                $results[] = $result;

                // Add small delay between chunks to be respectful
                if ($index < count($chunks) - 1) {
                    sleep(2); // Increased delay to be more respectful
                }
            } catch (\Exception $e) {
                Log::error("Failed to process chunk {$index}", [
                    'error' => $e->getMessage(),
                    'property_id' => $propertyId,
                ]);

                // If it's a rate limit error, we might want to pause longer
                if (str_contains($e->getMessage(), 'rate limit')) {
                    Log::warning("Rate limit hit during chunk processing, pausing for 60 seconds");
                    sleep(60);
                }

                throw $e;
            }
        }

        return $results;
    }

    /**
     * Get relevant headers from response for debugging
     */
    private function getRelevantHeaders($response): array
    {
        $relevantHeaders = [
            'Retry-After',
            'X-RateLimit-Limit',
            'X-RateLimit-Remaining',
            'X-RateLimit-Reset',
            'X-Request-ID',
        ];

        $headers = [];
        foreach ($relevantHeaders as $header) {
            $value = $response->header($header);
            if ($value !== null) {
                $headers[$header] = $value;
            }
        }

        return $headers;
    }

    public function generateOneTimeToken(Property $property): string
    {
        $payload = [
            'one_time_token' => [
                'property_id' => $property->external_id,
                'username' => $property->name,
            ],
        ];

        $response = Http::withHeaders([
            'user-api-key' => $this->apiKey,
        ])->post("{$this->baseUrl}/auth/one_time_token", $payload);

        if (!$response->successful()) {
            Log::error('Failed to generate Channex one-time token', [
                'property_id' => $property->external_id,
                'response' => $response->body(),
            ]);
            throw new \Exception('Failed to generate Channex access token');
        }

        $data = $response->json('data');
        return $data['token'];
    }

    public function handleBooking($data, $revisionId)
    {
        $rooms = $data['attributes']['rooms'] ?? [];
        $customerData = $data['attributes']['customer'] ?? [];

        // Check if property exists in database, if not ignore this booking
        $property = Property::where('external_id', $data['attributes']['property_id'])->first();
        if (!$property) {
            Log::info('Property not found in database, ignoring booking', [
                'property_external_id' => $data['attributes']['property_id'],
                'booking_id' => $data['attributes']['booking_id'] ?? null
            ]);
            return;
        }

        $email = $customerData['mail'] ?? null;

        if ($email) {
            $customer = Customer::firstOrCreate(
                ['email' => $email],
                [
                    'full_name' => ($customerData['name'] ?? '') . ' ' . ($customerData['surname'] ?? ''),
                    'phone' => $customerData['phone'] ?? '',
                    'address' => $customerData['address'] ?? '',
                    'country' => $customerData['country'] ?? '',
                    'city' => $customerData['city'] ?? '',
                    'type' => 'OTA',
                    'partner_group_id' => $property->partner_group_id ?? null,
                ]
            );
        } else {
            $customer = Customer::create([
                'full_name' => ($customerData['name'] ?? '') . ' ' . ($customerData['surname'] ?? ''),
                'phone' => $customerData['phone'] ?? '',
                'address' => $customerData['address'] ?? '',
                'country' => $customerData['country'] ?? '',
                'city' => $customerData['city'] ?? '',
                'type' => 'OTA',
                'partner_group_id' => $property->partner_group_id ?? null,
            ]);
        }

        $statusMap = [
            'new' => 'Mới',
            'modified' => 'Yêu cầu',
            'cancelled' => 'Hủy',
        ];

        $rawStatus = $data['attributes']['status'] ?? null;

        $checkIn = $data['attributes']['arrival_date'];
        $checkOut = $data['attributes']['departure_date'];

        // Create or update booking           
        $rawStatus = $data['attributes']['status'] ?? '';

        // if ($rawStatus === 'cancelled') {
        //     return;
        // }

        $isOtaCollect = $data['attributes']['payment_collect'] === 'ota';
        $payment_type = $isOtaCollect ? 'OTA Collect' : 'Hotel Collect';
        $room_payment_method = $isOtaCollect ? 'Thu bởi OTA' : 'Thu tại KS';
        $ota_fee = $isOtaCollect ? ($data['attributes']['ota_commission'] ?? 0) : null;
        $net_estimate = $isOtaCollect ? ($data['attributes']['amount'] - $ota_fee) : null;
        $ota_fee_percent = ($isOtaCollect && $data['attributes']['amount'] > 0)
            ? ($ota_fee / $data['attributes']['amount']) * 100
            : null;

        $isImported = isset($data['attributes']['meta']['is_imported']) && $data['attributes']['meta']['is_imported'];
        $otaName = $data['attributes']['ota_name'];
        if ($isImported) {
            $amount = 0;
        } else {
            if ($otaName === 'Expedia') {
                $amount = $isOtaCollect ? $this->getRemittanceAmount($data['attributes']['notes']) : ($data['attributes']['amount'] ?? 0);
            } else {
                $amount = $data['attributes']['amount'] ?? 0;
            }
        }

        $booking = Booking::updateOrCreate(
            ['external_id' => $data['attributes']['booking_id']],
            [
                'ota_name' => $otaName,
                'ota_reservation_code' => $data['attributes']['ota_reservation_code'] ?? null,
                'status' => $statusMap[$rawStatus] ?? 'Không xác định',
                'property_id' => $property->id,
                'customer_id' => $customer->id,
                'room_price_at_booking' => 0,
                'check_in_date' => $data['attributes']['arrival_date'],
                'check_out_date' => $data['attributes']['departure_date'],
                'check_in_time' => $property->checkin_from_time ?? '00:00:00',
                'check_out_time' => $property->checkout_to_time ?? '00:00:00',
                'payment_type' => $payment_type,
                'total_amount' => $amount,
                'customer_payment_amount' => $amount,
                'remaining' => $amount,
                'net_estimate' => $amount,
                'payout_received' => $amount,
                'room_payment_method' => $room_payment_method,
                'ota_fee' => 0,
                'ota_fee_percent' => 0,
                'adults' => $data['attributes']['occupancy']['adults'] ?? 0,
                'children' => $data['attributes']['occupancy']['children'] ?? 0,
                'newborn' => $data['attributes']['occupancy']['infants'] ?? 0,
                'reconciliation_status' => ($isOtaCollect && $amount > 0) ? 'Khớp' : 'Chờ payout',
                'difference_amount' => ($isOtaCollect && $amount > 0) ? 0 : null,
                'payment_status' => 'Chưa thanh toán',
                'is_imported' => $isImported,
                'meta' => $data['attributes']['meta'] ?? [],
                'currency' => $data['attributes']['currency'] ?? '',
                'channel_id' => $data['attributes']['channel_id'] ?? '',
                'notes' => $data['attributes']['notes'] ?? '',
                'payment_collect' => $data['attributes']['payment_collect'] ?? '',
                'payment_type_original' => $payment_type,
                'rooms' => $rooms,
                'raw_message' => $data['attributes']['raw_message'] ?? '',
            ]
        );

        BookingCustomer::where('booking_id', $booking->id)->delete();
        BookingCustomer::create([
            'booking_id' => $booking->id,
            'customer_id' => $customer->id,
        ]);

        // Xóa tất cả BookingRoom cũ của booking này
        BookingRoom::where('booking_id', $booking->id)->get()->each->delete();

        // Tạo lại BookingRoom mới dựa trên dữ liệu hiện tại
        foreach ($rooms as $room) {
            // Kiểm tra room_type_id có tồn tại không
            if (!isset($room['room_type_id'])) {
                Log::error('Missing room_type_id in booking data', [
                    'booking_id' => $data['attributes']['booking_id'] ?? null,
                    'room_data' => $room
                ]);
                continue;
            }

            $roomId = Room::where('external_id', $room['room_type_id'])->value('id');

            // Kiểm tra room có tồn tại không
            if (!$roomId) {
                Log::error('Room not found in database', [
                    'booking_id' => $data['attributes']['booking_id'] ?? null,
                    'room_type_id' => $room['room_type_id'],
                    'property_id' => $property->id
                ]);
                continue;
            }

            // Lấy rate_plan_id nội bộ từ external_id trả về từ Channex
            $ratePlanId = null;
            if (isset($room['rate_plan_id'])) {
                $ratePlanOTA = RatePlanOTA::where('external_id', $room['rate_plan_id'])->first();
                $ratePlanId = $ratePlanOTA && $ratePlanOTA->ratePlan ? $ratePlanOTA->ratePlan->id : null;
            }

            // Tìm room unit trống cho loại phòng này
            $roomUnitIds = RoomUnit::where('room_id', $roomId)->pluck('id');

            $conflictedRoomUnitIds = BookingRoom::whereIn('room_unit_id', $roomUnitIds)
                ->where('booking_id', '!=', $booking->id) // Loại trừ booking hiện tại
                ->where(function ($query) use ($checkIn, $checkOut) {
                    $query->where('check_in_date', '<', $checkOut)
                        ->where('check_out_date', '>', $checkIn);
                })
                ->pluck('room_unit_id');

            $availableRoomUnitId = $roomUnitIds
                ->diff($conflictedRoomUnitIds)
                ->shuffle()
                ->first();

            // Tạo BookingRoom mới
            // $nights = isset($room['days']) ? count($room['days']) : 0;
            $nights = isset($room['days']) ? count((array) $room['days']) : 0;

            $priceDate = collect($room['days'] ?? [])
                ->map(function ($price, $date) {
                    return $date . ':' . $price;
                })
                ->implode(',');

            BookingRoom::create([
                'booking_id' => $booking->id,
                'room_id' => $roomId,
                'room_unit_id' => $availableRoomUnitId,
                'property_id' => $property->id,
                'room_price_at_booking' => $room['amount'],
                'check_in_date' => $data['attributes']['arrival_date'],
                'check_out_date' => $data['attributes']['departure_date'],
                'check_in_time' => $property->checkin_from_time ?? '00:00:00',
                'check_out_time' => $property->checkout_to_time ?? '00:00:00',
                'rate_plan_id' => $ratePlanId,
                'night' => $nights,
                'price_date' => $priceDate,
                'total' => $room['amount'],
                'room_status' => 'Chưa nhận phòng',
            ]);
        }

        try {
            // Chỉ cập nhật booking từ property của partner id lưu trong .env
            if (config('services.pancake.partner_id') && $property->partner_group_id == config('services.pancake.partner_id')) {
                app(PancakeService::class)->syncBooking($booking);
            }
        } catch (\Throwable $th) {
            logger($th);
        }

        app(ChannexService::class)->acknowledgeBookingRevision($revisionId);
    }

    public function getRemittanceAmount(?string $notes): ?int
    {
        if (empty($notes)) {
            return 0;
        }

        if (preg_match('/Remittance amount:\s*([\d,]+)/i', $notes, $matches)) {
            return (int) str_replace(',', '', $matches[1]);
        }

        return 0;
    }

    public function getBookingsPage($propertyId = null, $page = 1, $limit = 100): array
    {
        $response = Http::timeout(60)->withHeaders([
            'user-api-key' => $this->apiKey,
        ])->get("{$this->baseUrl}/bookings", [
            'pagination[page]'  => $page,
            'pagination[limit]' => $limit,
            'order[inserted_at]' => 'asc',
            'filter[property_id]' => $propertyId,
            // 'filter[arrival_date][gte]' => '2025-06-01'
        ]);

        if (!$response->successful()) {
            throw new \Exception("Channex API error: " . $response->body());
        }

        return [
            'data' => $response->json('data', []),
            'meta' => $response->json('meta', [])
        ];
    }
}
