<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RatePlanRequest extends FormRequest
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
        $rules = [
            'room_id' => 'required|exists:rooms,id',
            'title' => 'required|string|max:255',
            'meal_type' => 'nullable|string|max:255',
            'sell_mode' => 'required|in:per_room,per_person',
            'children_fee' => 'nullable|numeric|min:0',
            'infant_fee' => 'nullable|numeric|min:0',
            'booking_source_ids' => 'nullable|array',
            'booking_source_ids.*' => 'exists:booking_sources,id',
        ];

        // Add rate_mode validation only for per_person mode
        if ($this->input('sell_mode') === 'per_person') {
            $rules['rate_mode'] = 'required|in:manual,auto';
        }

        // Add validation for restrictions
        $restrictions = ['max_stay', 'min_stay_arrival', 'min_stay_through'];
        foreach ($restrictions as $field) {
            $rules[$field] = 'nullable|integer|min:0';
        }

        // Add validation for boolean arrays
        $booleanArrays = ['closed_to_arrival', 'closed_to_departure', 'stop_sell'];
        foreach ($booleanArrays as $field) {
            $rules[$field] = 'nullable|array';
            $rules[$field . '.*'] = 'boolean';
        }

        // Add validation for auto rate settings (only for per_person + auto mode)
        if ($this->input('sell_mode') === 'per_person' && $this->input('rate_mode') === 'auto') {
            $rules['auto_rate_settings'] = 'required|array';
            $rules['auto_rate_settings.increase_mode'] = 'required|in:VND,%';
            $rules['auto_rate_settings.decrease_mode'] = 'required|in:VND,%';
            $rules['auto_rate_settings.increase_value'] = 'required|numeric|min:0';
            $rules['auto_rate_settings.decrease_value'] = 'required|numeric|min:0';
        }

        // Add validation for per_person mode
        if ($this->input('sell_mode') === 'per_person') {
            if ($this->input('rate_mode') === 'manual') {
                // For manual mode, occupancy_options should match room's max_people
                $rules['occupancy_options'] = 'required|array';
                $rules['occupancy_options.*.rate'] = 'required|numeric|min:0';
                $rules['occupancy_options.*.occupancy'] = 'required|numeric|min:0';
                $rules['occupancy_options.*.is_primary'] = 'required|boolean';
                $rules['primary_occupancy'] = 'required|integer|min:1';
                $rules['price'] = 'required|numeric|min:0';
                // Note: occupancy and is_primary will be set by the system, not user input
            } elseif ($this->input('rate_mode') === 'auto') {
                // For auto mode, auto_rate_settings is required
                $rules['auto_rate_settings'] = 'required|array';
                $rules['primary_occupancy'] = 'required|integer|min:1';
                $rules['price'] = 'required|numeric|min:0';
                $rules['auto_rate_settings.increase_mode'] = 'required|in:%,$';
                $rules['auto_rate_settings.decrease_mode'] = 'required|in:%,$';
                $rules['auto_rate_settings.increase_value'] = 'required|numeric|min:0';
                $rules['auto_rate_settings.decrease_value'] = 'required|numeric|min:0';
            }
        } else {
            // Per room mode - require price and primary_occupancy
            $rules['price'] = 'required|numeric|min:0';
            $rules['primary_occupancy'] = 'required|integer|min:1';
        }

        return $rules;
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages(): array
    {
        return [
            'room_id.required' => 'Loại phòng là bắt buộc.',
            'room_id.exists' => 'Loại phòng không tồn tại.',
            'title.required' => 'Tên rate plan là bắt buộc.',
            'title.max' => 'Tên rate plan không được vượt quá 255 ký tự.',
            'sell_mode.required' => 'Chế độ bán là bắt buộc.',
            'sell_mode.in' => 'Chế độ bán không hợp lệ.',
            'rate_mode.required' => 'Chế độ giá là bắt buộc.',
            'rate_mode.in' => 'Chế độ giá không hợp lệ.',
            'children_fee.numeric' => 'Phí trẻ em phải là số.',
            'children_fee.min' => 'Phí trẻ em không được nhỏ hơn 0.',
            'infant_fee.numeric' => 'Phí em bé phải là số.',
            'infant_fee.min' => 'Phí em bé không được nhỏ hơn 0.',
            'occupancy_options.required' => 'Tùy chọn occupancy là bắt buộc khi chế độ bán là per person.',
            'occupancy_options.*.rate.required' => 'Giá cho mỗi occupancy là bắt buộc.',
            'occupancy_options.*.rate.numeric' => 'Giá phải là số.',
            'occupancy_options.*.rate.min' => 'Giá không được nhỏ hơn 0.',
            'price.required' => 'Giá là bắt buộc.',
            'price.numeric' => 'Giá phải là số.',
            'price.min' => 'Giá không được nhỏ hơn 0.',
            'primary_occupancy.required' => 'Số lượng người chính là bắt buộc.',
            'primary_occupancy.integer' => 'Số lượng người chính phải là số nguyên.',
            'primary_occupancy.min' => 'Số lượng người chính phải lớn hơn 0.',
            'min_stay_arrival.integer' => 'Min stay arrival phải là số nguyên.',
            'min_stay_arrival.min' => 'Min stay arrival không được nhỏ hơn 0.',
            'min_stay_through.integer' => 'Min stay through phải là số nguyên.',
            'min_stay_through.min' => 'Min stay through không được nhỏ hơn 0.',
            'max_stay.integer' => 'Max stay phải là số nguyên.',
            'max_stay.min' => 'Max stay không được nhỏ hơn 0.',
            'auto_rate_settings.required' => 'Cài đặt tự động là bắt buộc khi chế độ giá là tự động.',
            'primary_occupancy.required' => 'Số lượng người chính là bắt buộc.',
            'primary_occupancy.integer' => 'Số lượng người chính phải là số nguyên.',
            'primary_occupancy.min' => 'Số lượng người chính phải lớn hơn 0.',

            'auto_rate_settings.increase_mode.required' => 'Chế độ tăng giá là bắt buộc.',
            'auto_rate_settings.increase_mode.in' => 'Chế độ tăng giá phải là % hoặc $.',
            'auto_rate_settings.decrease_mode.required' => 'Chế độ giảm giá là bắt buộc.',
            'auto_rate_settings.decrease_mode.in' => 'Chế độ giảm giá phải là % hoặc $.',
            'auto_rate_settings.increase_value.required' => 'Giá trị tăng là bắt buộc.',
            'auto_rate_settings.increase_value.numeric' => 'Giá trị tăng phải là số.',
            'auto_rate_settings.increase_value.min' => 'Giá trị tăng không được nhỏ hơn 0.',
            'auto_rate_settings.decrease_value.required' => 'Giá trị giảm là bắt buộc.',
            'auto_rate_settings.decrease_value.numeric' => 'Giá trị giảm phải là số.',
            'auto_rate_settings.decrease_value.min' => 'Giá trị giảm không được nhỏ hơn 0.',
        ];
    }
}
