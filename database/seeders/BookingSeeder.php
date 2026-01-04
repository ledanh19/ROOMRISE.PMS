<?php

namespace Database\Seeders;

use App\Models\Booking;
use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BookingSeeder extends Seeder
{
    public function run(): void
    {
        $otaChannels = ["Walk-in", "Từ đối tác"];
        $paymentMethods = [
            'Tiền mặt',
            'Chuyển khoản',
            'QR Code',
            '9Pay',
            'Momo',
            'VNPay',
            'Thẻ tín dụng',
        ];

        for ($i = 1; $i <= 20; $i++) {
            $propertyId = DB::table('properties')->inRandomOrder()->value('id');
            $roomId = DB::table('rooms')->where('property_id', $propertyId)->inRandomOrder()->value('id');
            $roomUnitId = DB::table('room_units')->where('room_id', $roomId)->inRandomOrder()->value('id');
            $customerId = DB::table('customers')->inRandomOrder()->value('id');

            if (!$propertyId || !$roomId || !$roomUnitId || !$customerId) {
                continue;
            }

            $checkIn = Carbon::now()->addDays(rand(-10, 10));
            $checkOut = (clone $checkIn)->addDays(rand(1, 5));

            $roomPrice = rand(1_000_000, 5_000_000);
            $otaFeePercent = rand(5, 20);
            $otaFee = round($roomPrice * ($otaFeePercent / 100));
            $netEstimate = $roomPrice - $otaFee;

            $paymentMethod = null;
            $isHotelCollect = rand(0, 1);
            if ($isHotelCollect) {
                // HOTEL COLLECT
                $paidCase = rand(0, 2); // 0: chưa trả, 1: đã cọc, 2: đã trả hết
                if ($paidCase === 0) {
                    $paid = 0;
                    $paymentStatus = 'Chưa thanh toán';
                } elseif ($paidCase === 1) {
                    $paid = rand(100_000, $roomPrice - 100_000);
                    $paymentStatus = 'Đã cọc';
                } else {
                    $paid = $roomPrice;
                    $paymentStatus = 'Đã thanh toán';
                }

                $remaining = max($roomPrice - $paid, 0);

                // Chỉ định phương thức thanh toán nếu có paid
                $paymentMethod = $paid > 0 ? $paymentMethods[array_rand($paymentMethods)] : null;

                $payoutReceived = $netEstimate + rand(-100_000, 100_000);
                $difference = $payoutReceived - $netEstimate;
                $reconciliationStatus = $difference === 0 ? 'Khớp' : 'Lệch';
                $paymentType = 'Hotel Collect';
            } else {
                // OTA COLLECT
                $paid = 0;
                $remaining = 0;
                $paymentMethod = null;

                $shouldReceive = rand(0, 2);
                if ($shouldReceive === 1) {
                    $payoutReceived = $netEstimate;
                    $difference = 0;
                    $reconciliationStatus = 'Khớp';
                } elseif ($shouldReceive === 2) {
                    $payoutReceived = $netEstimate - rand(50_000, 300_000);
                    $difference = $netEstimate - $payoutReceived;
                    $reconciliationStatus = 'Lệch';
                } else {
                    $payoutReceived = null;
                    $difference = null;
                    $reconciliationStatus = 'Chờ Payout';
                }

                $paymentStatus = 'Chưa thanh toán';
                $paymentType = 'OTA Collect';
            }

            Booking::create([
                'external_id' => 'BK' . str_pad($i, 6, '0', STR_PAD_LEFT),
                'ota_name' => $otaChannels[array_rand($otaChannels)],
                'status' => 'Xác nhận',
                'status_2' => 'Hoàn tất',
                'property_id' => $propertyId,
                'room_id' => $roomId,
                'room_unit_id' => $roomUnitId,
                'room_price_at_booking' => $roomPrice,
                'check_in_date' => $checkIn,
                'check_out_date' => $checkOut,
                'check_in_time' => '14:00',
                'check_out_time' => '12:00',
                'customer_id' => $customerId,
                'payment_type' => $paymentType,
                'payment_method' => $paymentMethod,
                'total_amount' => $roomPrice,
                'paid' => $paid,
                'remaining' => $remaining,
                'payment_status' => $paymentStatus,
                'ota_fee_percent' => $otaFeePercent,
                'ota_fee' => $otaFee,
                'net_estimate' => $netEstimate,
                'payout_received' => $payoutReceived,
                'difference_amount' => $difference,
                'ota_channel' => $otaChannels[array_rand($otaChannels)],
                'reconciliation_status' => $reconciliationStatus,
                'note' => 'Ghi chú booking #' . $i,
                'settlement_id' => null,
            ]);
        }
    }
}
