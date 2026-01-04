<?php

namespace App\Exports;

use App\Helpers\RoleHelper;
use App\Models\Booking;
use App\Models\IncomeExpense;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class HotelExportBooking implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    protected $filters;

    public function __construct($filters = [])
    {
        $this->filters = $filters;
    }

    public function collection()
    {
        // Normalize filters: accept Collection or array
        $filters = $this->filters instanceof Collection ? $this->filters->toArray() : (array) ($this->filters ?? []);

        $query = IncomeExpense::query()
            ->with([
                'booking.customer',
                'booking.bookingRooms.room',
                'booking.bookingRooms.roomUnit',
                'booking.property',
            ])
            ->whereHas('booking', function ($q) {
                $q->where('payment_type', 'Hotel Collect')
                    ->where('status', '!=', 'Hủy');
            });

        if (RoleHelper::isPartnerScopedUser()) {
            $query->where('partner_group_id', RoleHelper::getScopedPartnerGroupId());
        }

        if (!empty($filters) && array_values($filters) === $filters && is_numeric($filters[0])) {

            $ids = array_values(array_filter($filters, function ($v) {
                return is_numeric($v);
            }));
            if (!empty($ids)) {
                $query->whereIn('id', $ids);
            }
        } else {

            if (!empty($filters['range_date']) && str_contains($filters['range_date'], ' to ')) {
                [$start, $end] = explode(' to ', $filters['range_date']);
                $dateType = $filters['date_type'] ?? 'date';

                $query->when(in_array($dateType, ['check_in_date', 'check_out_date', 'created_at']), function ($q) use ($dateType, $start, $end) {
                    $q->whereHas('booking', function ($bookingQuery) use ($dateType, $start, $end) {
                        $bookingQuery->whereDate($dateType, '>=', $start)
                            ->whereDate($dateType, '<=', $end);
                    });
                }, function ($q) use ($start, $end) {
                    $q->whereDate('date', '>=', $start)
                        ->whereDate('date', '<=', $end);
                });
            }

            if (!empty($filters['ota_name'])) {
                $query->whereHas('booking', function ($bookingQuery) use ($filters) {
                    $bookingQuery->where('ota_name', $filters['ota_name']);
                });
            }

            if (!empty($filters['payment_method'])) {
                $query->where('payment_method', $filters['payment_method']);
            }

            if (!empty($filters['created_by'])) {
                $query->where('created_by', 'like', "%{$filters['created_by']}%");
            }

            if (!empty($filters['search'])) {
                $search = $filters['search'];
                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('payment_object', 'like', "%{$search}%")
                        ->orWhere('note', 'like', "%{$search}%")
                        ->orWhere('created_by', 'like', "%{$search}%")
                        ->orWhereHas('booking', function ($bookingQuery) use ($search) {
                            $bookingQuery->whereRaw("COALESCE(ota_reservation_code, CAST(id AS CHAR)) LIKE ?", ["%{$search}%"])
                                ->orWhereHas('customer', function ($customerQuery) use ($search) {
                                    $customerQuery->where('full_name', 'like', "%{$search}%")
                                        ->orWhere('phone', 'like', "%{$search}%");
                                });
                        });
                });
            }
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    public function map($income): array
    {
        $booking = $income->booking;

        return [
            $income->id,
            $booking?->ota_reservation_code ?: $booking?->id ?: '-',
            $booking?->ota_name ?? '-',
            $booking?->customer?->type ?? '-',
            $booking?->customer?->full_name ?? '-',
            $income->amount ?? 0,
            $income->payment_method ?? '-',
            $income->date ?? '-',
            $booking?->check_in_date ?? '-',
            $booking?->check_out_date ?? '-',
            $income->note ?? '-',
        ];
    }

    public function headings(): array
    {
        return [
            'Mã TT',
            'Mã đặt phòng',
            'Nguồn',
            'Phân loại',
            'Khách hàng',
            'Số tiền',
            'Phương thức',
            'Ngày thanh toán',
            'Nhận phòng',
            'Trả phòng',
            'Nội dung',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
            'F' => ['numberFormat' => ['formatCode' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1]],
            'H:J' => ['numberFormat' => ['formatCode' => 'dd/mm/yyyy']],
        ];
    }
}
