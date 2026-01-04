<?php

namespace App\Http\Requests;

use App\Models\Property;
use App\Models\Room;
use Illuminate\Foundation\Http\FormRequest;

class RoomRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'property_id' => 'required|exists:properties,id',
            // 'unit' => 'nullable|string|max:50',
            'quantity' => [
                'required',
                'integer',
                'min:1',
                function ($attribute, $value, $fail) {
                    $propertyId = request('property_id');
                    $roomId = $this->id; // Get room ID from route parameter


                    $query = Room::where('property_id', $propertyId);

                    // If updating (room ID exists), exclude current room
                    if ($roomId) {
                        $query->where('id', '!=', $roomId);
                    }

                    $totalExisting = $query->sum('quantity');
                    $max = Property::find($propertyId)?->max_rooms ?? 0;

                    if (($totalExisting + $value) > $max) {
                        $action = $roomId ? 'cập nhật' : 'thêm';
                        $fail("Tổng số lượng phòng không được vượt quá $max phòng cho chỗ nghỉ này. Số lượng phòng có thể $action là " . ($max - $totalExisting));
                    }
                }
            ],
            // 'max_people' => 'required|integer|min:1',
            'adults' => 'required|integer|min:0',
            'children' => 'required|integer|min:0',
            'room_units' => ['required', 'array'],
            'room_units.*.name' => ['required', 'string'],
            'room_units.*.id' => ['nullable', 'exists:room_units,id'],
        ];
    }
}
