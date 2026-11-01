<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RestaurantResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $weekly = $this->relationLoaded('schedules')
            ? app(\App\Services\ScheduleFormatterService::class)->weeklyHours($this->schedules)
            : [];

        return [
            'id'                 => $this->id,
            'name'               => $this->name,
            'formatted_schedule' => $this->formatted_schedule,
            'cuisine_type'       => $this->cuisine_type ?? 'General Dining',
            'address'            => $this->address ?? 'San Francisco, CA',
            'rating'             => (float) ($this->rating ?? 4.5),
            'description'        => $this->description,
            'is_open_now'        => (bool) $this->is_open_now,
            'open_days_count'    => count(array_filter($weekly, fn (array $day) => !$day['is_closed'])),
            'shifts_per_week'    => array_sum(array_column($weekly, 'shift_count')),
            'weekly_hours'       => $weekly,
            'created_at'         => $this->created_at?->toIso8601String(),
            'updated_at'         => $this->updated_at?->toIso8601String(),
        ];
    }
}
