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
        return [
            'id'                 => $this->id,
            'name'               => $this->name,
            'raw_schedule'       => $this->raw_schedule,
            'formatted_schedule' => $this->formatted_schedule,
            'cuisine_type'       => $this->cuisine_type ?? 'General Dining',
            'address'            => $this->address ?? 'San Francisco, CA',
            'rating'             => (float) ($this->rating ?? 4.5),
            'description'        => $this->description,
            'is_open_now'        => (bool) $this->is_open_now,
            'schedules'          => RestaurantScheduleResource::collection($this->whenLoaded('schedules')),
            'created_at'         => $this->created_at?->toIso8601String(),
            'updated_at'         => $this->updated_at?->toIso8601String(),
        ];
    }
}
