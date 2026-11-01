<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreRestaurantRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * Write actions (POST / PUT / DELETE) are guarded by the auth:sanctum
     * middleware on their routes. This extra check ensures a valid
     * authenticated user is present even if the route middleware is ever
     * removed or bypassed unintentionally.
     */
    public function authorize(): bool
    {
        // GET / public listing does not reach this Form Request.
        // Only mutating routes use StoreRestaurantRequest, so we can safely
        // require the user to be authenticated here as well.
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * The API only accepts structured shifts. `schedule_text` exists purely as
     * an optional import convenience for integrations that still carry the
     * original natural-language schedule; it is parsed into rows and never
     * persisted.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name'                   => ['required', 'string', 'max:255'],
            'address'                => ['nullable', 'string', 'max:255'],
            'cuisine_type'           => ['nullable', 'string', 'max:100'],
            'rating'                 => ['nullable', 'numeric', 'min:0', 'max:5'],
            'description'            => ['nullable', 'string', 'max:2000'],
            'schedule_text'          => ['nullable', 'string', 'max:1000'],
            'schedules'              => ['nullable', 'array'],
            'schedules.*.day_of_week'=> ['required_with:schedules', 'integer', 'between:1,7'],
            'schedules.*.open_time'  => ['required_with:schedules', 'string', 'regex:/^([01]?\d|2[0-3]):[0-5]\d(:[0-5]\d)?$/'],
            'schedules.*.close_time' => ['required_with:schedules', 'string', 'regex:/^([01]?\d|2[0-3]):[0-5]\d(:[0-5]\d)?$/'],
        ];
    }

    /**
     * A service window with identical start and end has no duration and cannot
     * match any query, so it is rejected instead of stored.
     *
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                $schedules = $this->input('schedules');

                if (!is_array($schedules)) {
                    return;
                }

                foreach ($schedules as $index => $schedule) {
                    $open = $schedule['open_time'] ?? null;
                    $close = $schedule['close_time'] ?? null;

                    if ($open !== null && $close !== null && $open === $close) {
                        $validator->errors()->add(
                            "schedules.{$index}.close_time",
                            'A service must end at a different time than it starts.'
                        );
                    }
                }
            },
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'A restaurant name is required.',
            'schedule_text.string' => 'The schedule import must be plain text.',
            'schedules.*.open_time.regex' => 'Opening times must use the HH:MM format.',
            'schedules.*.close_time.regex' => 'Closing times must use the HH:MM format.',
        ];
    }
}
