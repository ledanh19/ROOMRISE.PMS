<?php

namespace App\Exports;

use App\Models\IncomeExpense;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ExportIncomeExpense implements FromCollection, WithHeadings
{
    protected $type;
    protected $ids;

    protected $range_date;
    protected $category;
    protected $payment_method;
    protected $created_by;
    public function __construct($type = null, $range_date = null, $category = null, $payment_method = null, $created_by = null, $ids = null)
    {
        $this->type = $type;
        $this->ids = $ids;
        $this->range_date = $range_date;
        $this->category = $category;
        $this->payment_method = $payment_method;
        $this->created_by = $created_by;
    }
    public function collection()
    {

        $incomeExpense = IncomeExpense::query()

            ->when($this->range_date && str_contains($this->range_date, ' to '), function ($q) {
                [$start, $end] = explode(' to ', $this->range_date);
                $q->whereBetween('date', [$start, $end]);
            })
            ->when($this->ids, fn($q) => $q->whereIn('id', $this->ids))
            ->when($this->category, fn($q, $v) => $q->where('category', $v))
            ->when($this->payment_method, fn($q, $v) => $q->where('payment_method', $v))
            ->when($this->created_by, fn($q, $v) => $q->where('created_by', 'like', "%$v%"))
            ->when($this->type, fn($q, $v) => $q->where('type', $v))
            ->get()
            ->map(fn($item) => [
                'Ngày' => $item->date,
                'Loại' => $item->type == 'income' ? 'Thu ( Auto )' : 'Chi ( Tay )',
                'Danh mục' => $item->category . ' - ' . $item->subcategory,
                'PPTT Room' => $item->room_payment_method,
                'Hình thức thanh toán' => $item->payment_method,
                'Nguồn thanh toán' => $item->payment_source,
                'Đối tượng' => $item->payment_object,
                'Booking' => $item->payment_object,
                'Số tiền' => $item->amount,
                'Trạng thái' => $item->payment_status,
                'Tạo bởi' => $item->created_by,
                'Ghi chú' => $item->note,

            ]);        

        return $incomeExpense;
    }

    public function headings(): array
    {
        return [
            'Ngày',
            'Loại',
            'Danh mục',
            'PPTT Room',
            'Hình thức thanh toán',
            'Nguồn thanh toán',
            'Đối tượng',
            'Booking',
            'Số tiền',
            'Trạng thái',
            'Tạo bởi',
            'Ghi chú',
        ];
    }
}
