<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RestaurantScheduleResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'day_of_week'    => $this->day_of_week,
            'day_name'       => $this->day_name,
            'day_name_short' => $this->day_name_short,
            'open_time'      => substr($this->open_time, 0, 5), // 'HH:mm'
            'close_time'     => substr($this->close_time, 0, 5), // 'HH:mm'
            'spans_next_day' => (bool) $this->spans_next_day,
        ];
    }
}
