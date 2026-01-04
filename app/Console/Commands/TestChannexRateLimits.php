<?php

namespace App\Console\Commands;

use App\Services\ChannexService;
use App\Services\ChannexRateLimiter;
use Illuminate\Console\Command;

class TestChannexRateLimits extends Command
{
    protected $signature = 'channex:test-rate-limits {property_id}';
    protected $description = 'Test Channex API rate limit handling';

    public function handle(ChannexService $channexService, ChannexRateLimiter $rateLimiter)
    {
        $propertyId = $this->argument('property_id');

        $this->info("Testing Channex rate limits for property: {$propertyId}");
        $this->line('');

        // Check current usage
        $stats = $rateLimiter->getUsageStats($propertyId);
        $this->info("Current usage stats:");
        $this->table(
            ['Type', 'Current', 'Limit', 'Remaining'],
            [
                ['Global', $stats['global']['current'], $stats['global']['limit'], $stats['global']['remaining']],
                ['Property', $stats['property']['current'] ?? 0, $stats['property']['limit'] ?? 10, $stats['property']['remaining'] ?? 10],
            ]
        );

        // Test payload size validation
        $this->info("Testing payload size validation...");
        $smallPayload = ['values' => array_fill(0, 10, ['test' => 'data'])];
        $largePayload = ['values' => array_fill(0, 10000, ['test' => 'data'])];

        $this->line("Small payload valid: " . ($rateLimiter->isPayloadSizeValid($smallPayload) ? 'Yes' : 'No'));
        $this->line("Large payload valid: " . ($rateLimiter->isPayloadSizeValid($largePayload) ? 'Yes' : 'No'));

        // Test payload chunking
        $this->info("Testing payload chunking...");
        $testPayload = ['values' => array_fill(0, 250, ['test' => 'data'])];
        $chunks = $rateLimiter->splitPayload($testPayload, 100);
        $this->line("Original payload size: " . count($testPayload['values']));
        $this->line("Number of chunks: " . count($chunks));
        $this->line("Chunk sizes: " . implode(', ', array_map(fn($chunk) => count($chunk['values']), $chunks)));

        $this->info("Rate limit testing completed!");
    }
}
