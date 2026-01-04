<?php

namespace App\Jobs;

use App\Models\Property;
use App\Services\ChannexService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ChannexFullSyncJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 3600; // 1 hour
    public $tries = 3;

    protected $propertyId;

    public function __construct(int $propertyId)
    {
        $this->propertyId = $propertyId;
    }

    public function handle(ChannexService $channexService)
    {
        $property = Property::findOrFail($this->propertyId);

        Log::info("Starting Channex full sync job for property {$property->name}");

        // TODO:
        // Your full sync logic here
        // This will automatically use rate limiting

        Log::info("Completed Channex full sync job for property {$property->name}");
    }
}
