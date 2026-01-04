<?php

namespace App\Console\Commands;

use App\Services\ChannexService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessChannexBookingsCommand extends Command
{
    protected $signature = 'channex:process-bookings 
                            {--property-id= : Specific property ID to process}
                            {--page=1 : Starting page number}
                            {--limit=50 : Number of bookings per page}
                            {--max-pages=0 : Maximum pages to process (0 = unlimited)}
                            {--delay=5 : Delay between pages in seconds}';

    protected $description = 'Process Channex bookings with pagination to avoid timeout';

    public function handle(ChannexService $channexService)
    {
        $propertyId = $this->option('property-id');
        $startPage = (int) $this->option('page');
        $limit = (int) $this->option('limit');
        $maxPages = (int) $this->option('max-pages');
        $delay = (int) $this->option('delay');

        // Tăng memory limit và execution time
        ini_set('memory_limit', '1024M');
        set_time_limit(0);

        $this->info("Starting Channex bookings processing...");
        $this->info("Property ID: " . ($propertyId ?: 'All'));
        $this->info("Page: {$startPage}, Limit: {$limit}, Max Pages: " . ($maxPages ?: 'Unlimited'));

        $totalProcessed = 0;
        $totalSkipped = 0;
        $totalErrors = 0;
        $currentPage = $startPage;

        try {
            do {
                $this->info("\n--- Processing Page {$currentPage} ---");

                // Lấy bookings cho page hiện tại
                $response = $channexService->getBookingsPage($propertyId, $currentPage, $limit);

                if (empty($response['data'])) {
                    $this->info("No more bookings found on page {$currentPage}");
                    break;
                }

                $bookings = $response['data'];
                $meta = $response['meta'];

                $this->info("Found " . count($bookings) . " bookings on page {$currentPage}");

                $pageProcessed = 0;
                $pageSkipped = 0;
                $pageErrors = 0;

                // Xử lý từng booking
                foreach ($bookings as $index => $booking) {
                    $bookingId = $booking['attributes']['booking_id'] ?? 'Unknown';
                    $this->line("Processing booking " . ($index + 1) . "/" . count($bookings) . " (ID: {$bookingId})");

                    try {
                        DB::beginTransaction();
                        $channexService->handleBooking($booking, $booking['attributes']['revision_id']);
                        DB::commit();
                        $pageProcessed++;
                        $this->info("✓ Processed booking {$bookingId}");
                    } catch (\Exception $e) {
                        DB::rollBack();
                        $pageErrors++;

                        $errorMessage = $e->getMessage();
                        if (
                            strpos($errorMessage, 'Property not found') !== false ||
                            strpos($errorMessage, 'Room not found') !== false
                        ) {
                            $pageSkipped++;
                            $pageErrors--;
                            $this->warn("⚠ Skipped booking {$bookingId}: " . $errorMessage);
                        } else {
                            $this->error("✗ Error processing booking {$bookingId}: " . $errorMessage);
                        }
                    }
                }

                $totalProcessed += $pageProcessed;
                $totalSkipped += $pageSkipped;
                $totalErrors += $pageErrors;

                $this->info("Page {$currentPage} completed: {$pageProcessed} processed, {$pageSkipped} skipped, {$pageErrors} errors");

                // Kiểm tra xem còn page tiếp theo không
                $hasNext = isset($meta['total']) && ($currentPage * $meta['limit']) < $meta['total'];

                if ($hasNext && ($maxPages === 0 || $currentPage < $maxPages)) {
                    $currentPage++;
                    if ($delay > 0) {
                        $this->info("Waiting {$delay} seconds before processing next page...");
                        sleep($delay);
                    }
                } else {
                    break;
                }

                // Cleanup memory sau mỗi page
                unset($bookings, $response, $meta);
                gc_collect_cycles();
            } while (true);

            $this->info("\n=== Processing Complete ===");
            $this->info("Total processed: {$totalProcessed}");
            $this->info("Total skipped: {$totalSkipped}");
            $this->info("Total errors: {$totalErrors}");
            $this->info("Memory usage: " . memory_get_usage(true) / 1024 / 1024 . " MB");
        } catch (\Exception $e) {
            $this->error("Fatal error: " . $e->getMessage());
            Log::error("ProcessChannexBookingsCommand failed", [
                'error' => $e->getMessage(),
                'page' => $currentPage,
                'property_id' => $propertyId
            ]);
            return 1;
        }

        return 0;
    }
}
