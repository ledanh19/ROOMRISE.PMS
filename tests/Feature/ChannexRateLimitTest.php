<?php

namespace Tests\Feature;

use App\Services\ChannexRateLimiter;
use App\Services\ChannexService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ChannexRateLimitTest extends TestCase
{
    use RefreshDatabase;

    public function test_rate_limit_handling_with_body_response()
    {
        Http::fake([
            '*/restrictions' => Http::response([
                'errors' => [
                    'code' => 'too_many_requests',
                    'title' => 'Too Many Requests: retry after Sun, 13 Jul 2025 12:10:24 GMT',
                    'details' => [
                        'id' => '3aba33a1-db51-4892-afc1-acc7105aef94',
                        'retry_after' => 20
                    ]
                ]
            ], 429)
        ]);

        $channexService = app(ChannexService::class);
        $rateLimiter = app(ChannexRateLimiter::class);

        $testPayload = [
            'property_id' => 'test-property',
            'rate_plan_id' => 'test-rate-plan',
            'date' => '2025-01-01',
            'rate' => 100
        ];

        try {
            $channexService->updateRestrictions([$testPayload]);
            $this->fail('Expected exception was not thrown');
        } catch (\Exception $e) {
            $this->assertStringContainsString('rate limit', $e->getMessage());
        }
    }

    public function test_rate_limit_handling_with_header_response()
    {
        Http::fake([
            '*/restrictions' => Http::response([
                'errors' => [
                    'code' => 'too_many_requests',
                    'title' => 'Too Many Requests'
                ]
            ], 429, [
                'Retry-After' => '30',
                'X-RateLimit-Limit' => '10',
                'X-RateLimit-Remaining' => '0'
            ])
        ]);

        $channexService = app(ChannexService::class);
        $rateLimiter = app(ChannexRateLimiter::class);

        $testPayload = [
            'property_id' => 'test-property',
            'rate_plan_id' => 'test-rate-plan',
            'date' => '2025-01-01',
            'rate' => 100
        ];

        try {
            $channexService->updateRestrictions([$testPayload]);
            $this->fail('Expected exception was not thrown');
        } catch (\Exception $e) {
            $this->assertStringContainsString('rate limit', $e->getMessage());
        }
    }

    public function test_payload_chunking()
    {
        $rateLimiter = app(ChannexRateLimiter::class);

        $largePayload = [
            'values' => array_fill(0, 250, [
                'property_id' => 'test-property',
                'room_type_id' => 'test-room',
                'date' => '2025-01-01',
                'availability' => 10
            ])
        ];

        $chunks = $rateLimiter->splitPayload($largePayload, 100);

        $this->assertEquals(3, count($chunks));
        $this->assertEquals(100, count($chunks[0]['values']));
        $this->assertEquals(100, count($chunks[1]['values']));
        $this->assertEquals(50, count($chunks[2]['values']));
    }

    public function test_payload_size_validation()
    {
        $rateLimiter = app(ChannexRateLimiter::class);

        $smallPayload = ['values' => array_fill(0, 10, ['test' => 'data'])];
        $this->assertTrue($rateLimiter->isPayloadSizeValid($smallPayload));

        // Create a payload that exceeds 10MB
        $largePayload = ['values' => array_fill(0, 100000, ['test' => str_repeat('x', 100)])];
        $this->assertFalse($rateLimiter->isPayloadSizeValid($largePayload));
    }
}
