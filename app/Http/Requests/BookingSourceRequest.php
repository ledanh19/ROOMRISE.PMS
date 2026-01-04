<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BookingSourceRequest extends FormRequest
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
            'is_default' => 'boolean',
            'price_percentage' => 'required|numeric|min:0|max:1000',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Tên nguồn đặt phòng là bắt buộc.',
            'name.max' => 'Tên nguồn đặt phòng không được vượt quá 255 ký tự.',
            'price_percentage.required' => 'Phần trăm giá nguồn đặt phòng là bắt buộc.',
            'price_percentage.numeric' => 'Phần trăm giá nguồn đặt phòng phải là số.',
            'price_percentage.min' => 'Phần trăm giá nguồn đặt phòng không được nhỏ hơn 0.',
            'price_percentage.max' => 'Phần trăm giá nguồn đặt phòng không được lớn hơn 1000.',
        ];
    }
}
