<?php

namespace App\Console\Commands;

use App\Services\ChannexRateLimiter;
use Illuminate\Console\Command;

class MonitorChannexRateLimits extends Command
{
    protected $signature = 'channex:monitor-limits {property_id?}';
    protected $description = 'Monitor Channex API rate limits';

    public function handle(ChannexRateLimiter $rateLimiter)
    {
        $propertyId = $this->argument('property_id');

        $stats = $rateLimiter->getUsageStats($propertyId);

        $this->info('Channex Rate Limit Statistics:');
        $this->line('');

        $this->info('Global Limits:');
        $this->table(
            ['Metric', 'Value'],
            [
                ['Current Requests', $stats['global']['current']],
                ['Limit', $stats['global']['limit']],
                ['Remaining', $stats['global']['remaining']],
            ]
        );

        if (isset($stats['property'])) {
            $this->info('Property Limits:');
            $this->table(
                ['Metric', 'Value'],
                [
                    ['Property ID', $stats['property']['property_id']],
                    ['Current Requests', $stats['property']['current']],
                    ['Limit', $stats['property']['limit']],
                    ['Remaining', $stats['property']['remaining']],
                ]
            );
        }
    }
}
