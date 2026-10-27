<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRestaurantRequest extends FormRequest
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
            'name'                   => ['required', 'string', 'max:255'],
            'raw_schedule'           => ['nullable', 'string', 'max:1000'],
            'address'                => ['nullable', 'string', 'max:255'],
            'cuisine_type'           => ['nullable', 'string', 'max:100'],
            'rating'                 => ['nullable', 'numeric', 'min:0', 'max:5'],
            'description'            => ['nullable', 'string'],
            'schedules'              => ['nullable', 'array'],
            'schedules.*.day_of_week'=> ['required_with:schedules', 'integer', 'between:1,7'],
            'schedules.*.open_time'  => ['required_with:schedules', 'string', 'regex:/^\d{1,2}:\d{2}(:\d{2})?$/'],
            'schedules.*.close_time' => ['required_with:schedules', 'string', 'regex:/^\d{1,2}:\d{2}(:\d{2})?$/'],
            'schedules.*.spans_next_day' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Nama restoran wajib diisi.',
            'raw_schedule.string' => 'Jadwal operasional harus berupa teks.',
        ];
    }
}
