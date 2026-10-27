<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RestaurantSchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'restaurant_id',
        'day_of_week',
        'open_time',
        'close_time',
        'spans_next_day',
    ];

    protected $casts = [
        'day_of_week' => 'integer',
        'spans_next_day' => 'boolean',
    ];

    public const DAY_NAMES = [
        1 => 'Monday',
        2 => 'Tuesday',
        3 => 'Wednesday',
        4 => 'Thursday',
        5 => 'Friday',
        6 => 'Saturday',
        7 => 'Sunday',
    ];

    public const DAY_NAMES_SHORT = [
        1 => 'Mon',
        2 => 'Tue',
        3 => 'Wed',
        4 => 'Thu',
        5 => 'Fri',
        6 => 'Sat',
        7 => 'Sun',
    ];

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function getDayNameAttribute(): string
    {
        return self::DAY_NAMES[$this->day_of_week] ?? 'Unknown';
    }

    public function getDayNameShortAttribute(): string
    {
        return self::DAY_NAMES_SHORT[$this->day_of_week] ?? 'Unknown';
    }

    /**
     * Scope a query to only include schedules that are open at a given day of week and time.
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $dayOfWeek 1 (Monday) to 7 (Sunday)
     * @param string $time Format 'H:i:s' or 'H:i'
     */
    public function scopeIsOpenAt($query, int $dayOfWeek, string $time)
    {
        // Calculate previous day of week (1 to 7)
        $prevDay = ($dayOfWeek === 1) ? 7 : $dayOfWeek - 1;

        return $query->where(function ($q) use ($dayOfWeek, $time, $prevDay) {
            // Case 1: Standard shift starting on $dayOfWeek and NOT spanning next day
            $q->where(function ($sub) use ($dayOfWeek, $time) {
                $sub->where('day_of_week', $dayOfWeek)
                    ->where('spans_next_day', false)
                    ->where('open_time', '<=', $time)
                    ->where('close_time', '>=', $time);
            })
            // Case 2: Shift starting on $dayOfWeek, DOES span next day, and current time is before midnight
            ->orWhere(function ($sub) use ($dayOfWeek, $time) {
                $sub->where('day_of_week', $dayOfWeek)
                    ->where('spans_next_day', true)
                    ->where('open_time', '<=', $time);
            })
            // Case 3: Shift started on PREVIOUS day, DOES span next day, and current time is after midnight (early morning)
            ->orWhere(function ($sub) use ($prevDay, $time) {
                $sub->where('day_of_week', $prevDay)
                    ->where('spans_next_day', true)
                    ->where('close_time', '>=', $time);
            });
        });
    }
}
