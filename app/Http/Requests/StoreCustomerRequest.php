<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCustomerRequest extends FormRequest
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
            'full_name'   => ['required', 'string', 'max:255'],
            'type'        => ['required', Rule::in(['Sale', 'Sale TA', 'OTA', 'Social', 'Walk-in', 'Từ đối tác'])],
            'email'       => ['nullable', 'email'],
            'partner_id'  => ['nullable', 'exists:partners,id'],
            'phone'       => ['nullable', 'string', 'max:20'],
            'id_type'     => ['nullable', Rule::in(['CCCD/CMND', 'Hộ chiếu', 'Bằng lái xe', 'Khác'])],
            'id_number'   => ['nullable', 'string', 'max:100'],
            'dob'         => ['nullable', 'date'],
            // 'nationality' => ['nullable', 'string', 'max:100'],
            'country'     => ['nullable', 'string', 'max:100'],
            'address'     => ['nullable', 'string', 'max:255'],
            'city'        => ['nullable', 'string', 'max:100'],
            'image'       => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ];
    }
}
