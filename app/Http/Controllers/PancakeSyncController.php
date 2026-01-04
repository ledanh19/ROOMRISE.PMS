<?php

namespace App\Http\Controllers;

use App\Models\Property;
use App\Services\PancakeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class PancakeSyncController extends Controller
{
    protected $pancakeService;

    public function __construct(PancakeService $pancakeService)
    {
        $this->pancakeService = $pancakeService;
    }

    /**
     * Đồng bộ tất cả booking của partner vào Pancake CRM
     */
    public function syncAll(Request $request)
    {
        // Chỉ Admin mới có thể thực hiện
        if (!Auth::user()->hasRole('Admin') && !Auth::user()->hasRole('Super Admin')) {
            abort(403, 'Unauthorized action.');
        }

        $partnerId = config('services.pancake.partner_id');

        if (!$partnerId) {
            return response()->json([
                'success' => false,
                'message' => 'Chưa cấu hình partner_id cho Pancake'
            ], 400);
        }

        try {
            $result = $this->performSync($partnerId);

            Log::info('Pancake sync completed', [
                'user_id' => Auth::id(),
                'result' => $result
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Đồng bộ hoàn tất',
                'data' => $result
            ]);
        } catch (\Exception $e) {
            Log::error('Pancake sync failed', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Đồng bộ thất bại: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Thực hiện đồng bộ
     */
    protected function performSync($partnerId)
    {
        $properties = Property::where('partner_group_id', $partnerId)->get();

        $results = [
            'total' => 0,
            'success' => 0,
            'failed' => 0
        ];

        foreach ($properties as $property) {
            $bookings = $property->bookings()
                ->whereNull('pancake_id')
                ->whereNotNull('external_id')
                ->with(['customer', 'bookingRooms.room'])
                ->get();

            foreach ($bookings as $booking) {
                $results['total']++;

                try {
                    $response = $this->pancakeService->syncBooking($booking);

                    if ($response && isset($response['record']['id'])) {
                        $results['success']++;
                        Log::info('Booking synced successfully', [
                            'booking_id' => $booking->id,
                            'pancake_id' => $response['record']['id']
                        ]);
                    } else {
                        $results['failed']++;
                        Log::warning('Booking sync failed - no pancake_id received', [
                            'booking_id' => $booking->id
                        ]);
                    }
                } catch (\Exception $e) {
                    $results['failed']++;
                    Log::error('Booking sync error', [
                        'booking_id' => $booking->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }
        }

        return $results;
    }
}
