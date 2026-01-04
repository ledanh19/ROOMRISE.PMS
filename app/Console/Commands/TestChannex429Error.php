<?php

namespace App\Console\Commands;

use App\Services\ChannexService;
use App\Services\ChannexRateLimiter;
use Illuminate\Console\Command;

class TestChannex429Error extends Command
{
    protected $signature = 'channex:test-429 
                            {property_id : Channex property UUID}
                            {rate_plan_id : Channex rate plan UUID}
                            {--requests=15 : Number of requests to send (default: 15)}
                            {--delay=0 : Delay between requests in seconds (default: 0)}
                            {--type=restrictions : Request type: restrictions or availability}';

    protected $description = 'Test Channex 429 rate limit error by sending multiple requests';

    public function handle(ChannexService $channexService, ChannexRateLimiter $rateLimiter)
    {
        $propertyId = $this->argument('property_id');
        $ratePlanId = $this->argument('rate_plan_id');
        $numRequests = (int) $this->option('requests');
        $delay = (int) $this->option('delay');
        $type = $this->option('type');

        $this->info("Testing Channex 429 error for property: {$propertyId}");
        $this->info("Rate plan: {$ratePlanId}");
        $this->info("Number of requests: {$numRequests}");
        $this->info("Delay between requests: {$delay}s");
        $this->info("Request type: {$type}");
        $this->line('');

        // Check current rate limit status
        $stats = $rateLimiter->getUsageStats($propertyId);
        $this->info("Current rate limit status:");
        $this->table(
            ['Type', 'Current', 'Limit', 'Remaining'],
            [
                ['Global', $stats['global']['current'], $stats['global']['limit'], $stats['global']['remaining']],
                ['Property', $stats['property']['current'] ?? 0, $stats['property']['limit'] ?? 10, $stats['property']['remaining'] ?? 10],
            ]
        );
        $this->line('');

        // Confirm before proceeding
        if (!$this->confirm('Do you want to proceed with sending requests? This may trigger rate limits.')) {
            $this->info('Test cancelled.');
            return;
        }

        $successCount = 0;
        $errorCount = 0;
        $rateLimitCount = 0;

        $this->info("Starting to send {$numRequests} requests...");
        $this->line('');

        for ($i = 0; $i < $numRequests; $i++) {
            try {
                $date = now()->addDays($i)->format('Y-m-d');
                $rate = 100000 + $i;

                if ($type === 'availability') {
                    $payload = [
                        [
                            'property_id' => $propertyId,
                            'room_type_id' => $ratePlanId, // Using rate_plan_id as room_type_id for test
                            'date' => $date,
                            'availability' => 10 + $i,
                        ]
                    ];
                    $channexService->updateAvailability($payload);
                } else {
                    $payload = [
                        [
                            'property_id' => $propertyId,
                            'rate_plan_id' => $ratePlanId,
                            'date' => $date,
                            'rate' => $rate,
                        ]
                    ];
                    $channexService->updateRestrictions($payload);
                }

                $successCount++;
                $this->line("✓ Request {$i}: SUCCESS (Rate: {$rate}, Date: {$date})");
            } catch (\Exception $e) {
                $errorCount++;
                $errorMessage = $e->getMessage();

                if (str_contains($errorMessage, 'rate limit') || str_contains($errorMessage, '429')) {
                    $rateLimitCount++;
                    $this->error("✗ Request {$i}: RATE LIMIT ERROR - {$errorMessage}");
                } else {
                    $this->error("✗ Request {$i}: OTHER ERROR - {$errorMessage}");
                }
            }

            // Add delay between requests if specified
            if ($delay > 0 && $i < $numRequests - 1) {
                $this->line("Waiting {$delay} seconds...");
                sleep($delay);
            }
        }

        $this->line('');
        $this->info("Test completed!");
        $this->table(
            ['Result', 'Count'],
            [
                ['Successful', $successCount],
                ['Rate Limit Errors', $rateLimitCount],
                ['Other Errors', $errorCount - $rateLimitCount],
                ['Total', $numRequests],
            ]
        );

        // Show final rate limit status
        $finalStats = $rateLimiter->getUsageStats($propertyId);
        $this->line('');
        $this->info("Final rate limit status:");
        $this->table(
            ['Type', 'Current', 'Limit', 'Remaining'],
            [
                ['Global', $finalStats['global']['current'], $finalStats['global']['limit'], $finalStats['global']['remaining']],
                ['Property', $finalStats['property']['current'] ?? 0, $finalStats['property']['limit'] ?? 10, $finalStats['property']['remaining'] ?? 10],
            ]
        );

        if ($rateLimitCount > 0) {
            $this->line('');
            $this->warn("Rate limit was triggered! Check logs for retry details.");
            $this->info("Rate limits will reset automatically after 1 minute.");
        }
    }
}
