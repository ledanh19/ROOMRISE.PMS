<?php

namespace App\Services;

use App\Models\Booking;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PancakeService
{
    protected $baseUrl;
    protected $apiKey;
    protected $shopId;
    protected $pancakeCrmTable;

    public function __construct()
    {
        $this->baseUrl = 'https://pos.pages.fm/api/v1';
        $this->apiKey = config('services.pancake.api_key');
        $this->shopId = config('services.pancake.shop_id');
        $this->pancakeCrmTable = config('services.pancake.pancake_crm_table');
    }

    /**
     * Tạo booking mới trong Pancake CRM
     */
    public function createBooking(Booking $booking): ?array
    {
        try {
            $data = $this->prepareBookingData($booking);

            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->post($this->getEndpoint(), $data);

            if ($response->successful()) {
                $responseData = $response->json();
                Log::info('Booking created successfully in Pancake CRM', [
                    'booking_id' => $booking->id,
                    'pancake_response' => $responseData
                ]);

                // Cập nhật pancake_id vào booking nếu có
                if (isset($responseData['record']['id'])) {
                    $booking->update(['pancake_id' => $responseData['record']['id']]);
                }

                return $responseData;
            }

            Log::error('Failed to create booking in Pancake CRM', [
                'booking_id' => $booking->id,
                'response' => $response->body(),
                'status' => $response->status(),
                'data' => $data
            ]);

            return null;
        } catch (\Exception $e) {
            logger($e);
            Log::error('Exception when creating booking in Pancake CRM', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage(),
                'data' => $data
            ]);
            return null;
        }
    }

    /**
     * Cập nhật booking trong Pancake CRM
     */
    public function updateBooking(Booking $booking): ?array
    {
        try {
            if (!$booking->pancake_id) {
                Log::warning('Cannot update booking in Pancake CRM - missing pancake_id', [
                    'booking_id' => $booking->id
                ]);
                return null;
            }

            $data = $this->prepareBookingData($booking);

            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->put($this->getEndpoint(), $data);

            if ($response->successful()) {
                $responseData = $response->json();
                Log::info('Booking updated successfully in Pancake CRM', [
                    'booking_id' => $booking->id,
                    'pancake_id' => $booking->pancake_id,
                    'pancake_response' => $responseData
                ]);

                return $responseData;
            }

            Log::error('Failed to update booking in Pancake CRM', [
                'booking_id' => $booking->id,
                'pancake_id' => $booking->pancake_id,
                'response' => $response->body(),
                'status' => $response->status(),
                'data' => $data
            ]);

            return null;
        } catch (\Exception $e) {
            logger($e);
            Log::error('Exception when updating booking in Pancake CRM', [
                'booking_id' => $booking->id,
                'pancake_id' => $booking->pancake_id,
                'error' => $e->getMessage(),
                'data' => $data
            ]);
            return null;
        }
    }

    /**
     * Tạo hoặc cập nhật booking trong Pancake CRM
     */
    public function syncBooking(Booking $booking): ?array
    {
        if ($booking->pancake_id) {
            return $this->updateBooking($booking);
        } else {
            return $this->createBooking($booking);
        }
    }

    /**
     * Chuẩn bị dữ liệu booking để gửi đến Pancake CRM
     */
    protected function prepareBookingData(Booking $booking): array
    {
        $customer = $booking->customer;
        $property = $booking->property;

        // Xác định payment type và policy
        $paymentMethod = $booking->room_payment_method ?? '';
        $policy = $booking->payment_type;

        // Tính toán các giá trị
        $totalAmount = $booking->total_amount ?? 0;
        $netEstimate = $booking->net_estimate ?? $totalAmount;
        $otaFee = $booking->ota_fee ?? 0;
        $commission = $totalAmount - $netEstimate;

        // Xác định status
        $status = $this->mapBookingStatus($booking->status);

        // Xác định source/lead
        $source = $this->mapBookingSource($booking->ota_name ?? '');

        $data = [
            'record' => [
                'id' => $booking->pancake_id ?? null,
                'Name' => $customer->full_name ?? '',
                // 'doi_tuong' => config('services.pancake.customer_type_id', 'KH MỚI-493f-8076-1a62-2801-2d24-389b-85c2'),
                'doi_tuong' => null,
                'ma_dat_phong' => $booking->ota_reservation_code ?? $booking->id ?? '',
                'so_ngay' => \Carbon\Carbon::parse($booking->check_in_date)->diffInDays(\Carbon\Carbon::parse($booking->check_out_date)),
                'SourceOfLeads' => [$source],
                'Status' => $status,
                'ten_cho_nghi' => $property->name ?? '',
                'loai_phong' => $this->getRoomTypesString($booking),
                'quoc_gia' => $customer->country ?? '',
                'email' => $customer->email ?? '',
                'sdt' => $customer->phone ?? '',
                'ngay_nhan_phong' => $booking->check_in_date,
                'ngay_tra_phong' => $booking->check_out_date,
                'thoi_diem_dat_phong' => $booking->created_at ?? now(),
                'tong_tien_dat_phong' => $totalAmount,
                'doanh_thu' => $netEstimate,
                'hoa_hong' => $commission,
                'hinh_thuc_thanh_toan' => $paymentMethod,
                'phuong_thuc_nhan_thanh_toan' => $policy,
            ]
        ];

        return $data;
    }

    /**
     * Lấy endpoint API Pancake
     */
    protected function getEndpoint(): string
    {
        return "{$this->baseUrl}/shops/{$this->shopId}/crm/{$this->pancakeCrmTable}/records?api_key={$this->apiKey}";
    }

    /**
     * Map trạng thái booking sang Pancake status
     */
    protected function mapBookingStatus(string $status): string
    {
        $statusMap = [
            'Mới' => config('services.pancake.status_new'),
            // 'Yêu cầu' => config('services.pancake.status_modified'),
            'Hủy' => config('services.pancake.status_cancelled'),
            // 'standby' => config('services.pancake.status_standby'),
        ];

        return $statusMap[$status] ?? config('services.pancake.status_new');
    }

    /**
     * Map source booking sang Pancake source ID
     */
    protected function mapBookingSource(string $otaName): ?string
    {
        if (empty($otaName)) {
            return null;
        }

        $sourceMap = [
            'BookingCom' => config('services.pancake.source_booking', ''),
            'Expedia' => config('services.pancake.source_expedia', ''),
            'Agoda' => config('services.pancake.source_agoda', ''),
            'CTrip' => config('services.pancake.source_ctrip', ''),
            'Airbnb' => config('services.pancake.source_airbnb', ''),
        ];

        return $sourceMap[$otaName] ?? null;
    }

    /**
     * Lấy chuỗi loại phòng từ booking
     */
    protected function getRoomTypesString(Booking $booking): string
    {
        return $booking->bookingRooms
            ->map(function ($bookingRoom) {
                return $bookingRoom->room->name ?? '';
            })
            ->filter()
            ->implode('+++');
    }

    /**
     * Xóa booking khỏi Pancake CRM (nếu cần)
     */
    public function deleteBooking(Booking $booking): bool
    {
        try {
            if (!$booking->pancake_id) {
                return false;
            }

            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->delete($this->getEndpoint() . "&id={$booking->pancake_id}");

            if ($response->successful()) {
                Log::info('Booking deleted successfully from Pancake CRM', [
                    'booking_id' => $booking->id,
                    'pancake_id' => $booking->pancake_id
                ]);

                // Xóa pancake_id khỏi booking
                $booking->update(['pancake_id' => null]);

                return true;
            }

            Log::error('Failed to delete booking from Pancake CRM', [
                'booking_id' => $booking->id,
                'pancake_id' => $booking->pancake_id,
                'response' => $response->body()
            ]);

            return false;
        } catch (\Exception $e) {
            Log::error('Exception when deleting booking from Pancake CRM', [
                'booking_id' => $booking->id,
                'pancake_id' => $booking->pancake_id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
}
