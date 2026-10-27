<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Restaurant extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'raw_schedule',
        'address',
        'cuisine_type',
        'rating',
        'description',
    ];

    protected $casts = [
        'rating' => 'float',
    ];

    protected $appends = [
        'is_open_now',
        'formatted_schedule',
    ];

    public function schedules(): HasMany
    {
        return $this->hasMany(RestaurantSchedule::class)->orderBy('day_of_week')->orderBy('open_time');
    }

    /**
     * Scope to filter restaurants open at a specific day of week and time.
     * 
     * @param Builder $query
     * @param int $dayOfWeek 1 (Monday) to 7 (Sunday)
     * @param string $time 'H:i' or 'H:i:s'
     */
    public function scopeOpenAt(Builder $query, int $dayOfWeek, string $time): Builder
    {
        return $query->whereHas('schedules', function ($q) use ($dayOfWeek, $time) {
            $q->isOpenAt($dayOfWeek, $time);
        });
    }

    /**
     * Scope to filter restaurants open on a specific day of week (at any time).
     * 
     * @param Builder $query
     * @param int $dayOfWeek 1 (Monday) to 7 (Sunday)
     */
    public function scopeOpenOnDay(Builder $query, int $dayOfWeek): Builder
    {
        $prevDay = ($dayOfWeek === 1) ? 7 : $dayOfWeek - 1;

        return $query->whereHas('schedules', function ($q) use ($dayOfWeek, $prevDay) {
            $q->where('day_of_week', $dayOfWeek)
              ->orWhere(function ($sub) use ($prevDay) {
                  $sub->where('day_of_week', $prevDay)
                      ->where('spans_next_day', true);
              });
        });
    }

    /**
     * Check if the restaurant is currently open right now.
     */
    public function getIsOpenNowAttribute(): bool
    {
        $now = Carbon::now();
        $currentDay = (int) $now->isoFormat('E'); // 1 (Mon) - 7 (Sun)
        $currentTime = $now->format('H:i:s');

        if ($this->relationLoaded('schedules')) {
            $prevDay = ($currentDay === 1) ? 7 : $currentDay - 1;
            return $this->schedules->contains(function ($schedule) use ($currentDay, $currentTime, $prevDay) {
                // Case 1: Standard shift
                if ($schedule->day_of_week === $currentDay && !$schedule->spans_next_day) {
                    return $schedule->open_time <= $currentTime && $schedule->close_time >= $currentTime;
                }
                // Case 2: Spans midnight, currently evening/night
                if ($schedule->day_of_week === $currentDay && $schedule->spans_next_day) {
                    return $currentTime >= $schedule->open_time;
                }
                // Case 3: Started previous day, spans midnight, currently early morning
                if ($schedule->day_of_week === $prevDay && $schedule->spans_next_day) {
                    return $currentTime <= $schedule->close_time;
                }
                return false;
            });
        }

        return $this->schedules()->isOpenAt($currentDay, $currentTime)->exists();
    }

    /**
     * Human-friendly formatted schedule string.
     */
    public function getFormattedScheduleAttribute(): string
    {
        if (!empty($this->raw_schedule)) {
            return $this->raw_schedule;
        }

        if (!$this->relationLoaded('schedules') || $this->schedules->isEmpty()) {
            return 'Hours not specified';
        }

        // Group schedules by hours
        $grouped = [];
        foreach ($this->schedules as $schedule) {
            $open = Carbon::createFromFormat('H:i:s', $schedule->open_time)->format('g:i a');
            $close = Carbon::createFromFormat('H:i:s', $schedule->close_time)->format('g:i a');
            $timeStr = "{$open} - {$close}";
            $grouped[$timeStr][] = $schedule->day_name_short;
        }

        $parts = [];
        foreach ($grouped as $timeStr => $days) {
            $dayList = implode(', ', $days);
            $parts[] = "{$dayList} {$timeStr}";
        }

        return implode(' / ', $parts);
    }
}
