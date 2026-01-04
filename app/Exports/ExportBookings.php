<?php

namespace App\Exports;

use App\Models\Booking;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ExportBookings implements FromCollection, WithHeadings
{
    protected $date_type;
    protected $range_date;
    protected $status;
    protected $payment_type;
    protected $payment_status;
    protected $room_type;

    public function __construct($date_type = null, $range_date = null, $status = null, $payment_type = null, $payment_status = null, $room_type = null)
    {
        $this->date_type = $date_type;
        $this->range_date = $range_date;
        $this->status = $status;
        $this->payment_type = $payment_type;
        $this->payment_status = $payment_status;
        $this->room_type = $room_type;
    }
    public function collection()
    {
        if (is_string($this->range_date) && str_contains($this->range_date, ' to ')) {
            $this->range_date = explode(' to ', $this->range_date);
        }
        $query = Booking::with(['property', 'room', 'roomUnit', 'customer', 'bookingRooms.room', 'bookingRooms.roomUnit'])
            ->when($this->date_type && is_array($this->range_date) && count($this->range_date) === 2, function ($query) {
                $startDate = $this->range_date[0];
                $endDate = $this->range_date[1];

                if (in_array($this->date_type, ['check_in_date', 'check_out_date', 'created_at'])) {
                    $query->whereBetween($this->date_type, [$startDate, $endDate]);
                }
            })
            ->when($this->room_type, function ($query) {
                $query->where('room_id', $this->room_type);
            })
            ->when($this->payment_type, function ($query) {
                $query->where('payment_type', $this->payment_type);
            })
            ->when($this->payment_status, function ($query) {
                $query->where('payment_status', $this->payment_status);
            })
            ->when($this->status, function ($query) {
                $query->where('status', $this->status);
            })
            ->get()
            ->map(function ($booking) {
                return [
                    'id' => $booking->id,
                    'payment_type' => $booking->payment_type,
                    'full_name' => optional($booking->customer)->full_name,
                    'phone' => optional($booking->customer)->phone,
                    'id_number' => optional($booking->customer)->id_number,
                    'check_in_date' => $booking->check_in_date,
                    'check_out_date' => $booking->check_out_date,
                    'room' => $booking->bookingRooms->pluck('room.name')->filter()->unique()->implode(', '),
                    'room_unit' => $booking->bookingRooms->pluck('roomUnit.name')->filter()->unique()->implode(', '),
                    'status' => $booking->status,
                    'payment_status' => $booking->payment_status,
                    'total_amount' => $booking->total_amount,
                    'paid' => $booking->paid,
                    'remaining' => $booking->remaining,
                    'created_at' => $booking->created_at,
                    'note' => $booking->note,
                ];
            });

        return $query;
    }
    public function headings(): array
    {
        return [
            'Mã đặt phòng',
            'Phân loại',
            'Khách hàng',
            'SĐT',
            'Số định danh',
            'Nhận phòng',
            'Trả phòng',
            'Loại phòng',
            'Phòng',
            'Trạng thái',
            'TT Thanh toán',
            'Tiền phòng',
            'Đã thanh toán',
            'Còn lại',
            'Ngày đặt',
            'Ghi chú',

        ];
    }
}
