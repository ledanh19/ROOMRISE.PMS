<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookingItemResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status,
            'currency' => optional($this->property)->currency,
            'status_2' => $this->status_2,
            'property_id' => $this->property_id,
            'property_name' => optional($this->property)->name,
            'room_id' => $this->room_id,
            'room_name' => optional($this->room)->name,
            'room_unit_id' => $this->room_unit_id,
            'room_unit_name' => optional($this->roomUnit)->name,
            'room_price_at_booking' => $this->room_price_at_booking,
            'check_in_datetime' => $this->check_in_date . 'T' . $this->check_in_time,
            'check_out_datetime' => $this->check_out_date . 'T' . $this->check_out_time,
            'check_in_date' => $this->check_in_date,
            'check_out_date' => $this->check_out_date,
            'check_in_time' => $this->check_in_time,
            'check_out_time' => $this->check_out_time,
            'customer_id' => optional($this->customer)->id,
            'customer_name' => optional($this->customer)->full_name,
            'payment_status' => $this->payment_status,
            'total_amount' => $this->total_amount,
            'paid' => $this->paid,
            'remaining' => $this->remaining,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'total_amount' => $this->total_amount,
            'ota_name' => $this->ota_name,
            'booking_code' => $this->booking_code,
            'adults' => $this->adults,
            'children' => $this->children,
            'newborn' => $this->newborn,
            'room_payment_method' => $this->room_payment_method,
            'rooms' => $this->bookingRooms->map(function ($bookingRoom) {
                return [
                    'id' => $bookingRoom->id,
                    'room_price_at_booking' => $bookingRoom->room_price_at_booking,
                    'room' => [
                        'id' => $bookingRoom->room->id,
                        'name' => $bookingRoom->room->name,
                    ],
                    'room_unit' => [
                        'id' => $bookingRoom?->roomUnit?->id ?? null,
                        'name' => $bookingRoom?->roomUnit->name ?? '-',
                    ],
                ];
            }),
            'property' => $this->property->name ?? '-',
            // Hotel Income Expenses (Hotel Collect)
            'income_expenses' => $this->whenLoaded('incomeExpenses', function () {
                return $this->incomeExpenses->map(function ($incomeExpense) {
                    return [
                        'id' => $incomeExpense->id,
                        'date' => $incomeExpense->date,
                    ];
                });
            }),

            // Partner Income Expenses (Partner Collect) 
            'partner_income_expenses' => $this->whenLoaded('partnerIncomeExpenses', function () {
                return $this->partnerIncomeExpenses->map(function ($partnerIncomeExpense) {
                    return [
                        'id' => $partnerIncomeExpense->id,
                        'date' => $partnerIncomeExpense->date,
                    ];
                });
            }),
            'partner_name' => optional($this->customer?->partner)->name ?? '-',
            'is_imported' => $this->is_imported,
        ];
    }
}
