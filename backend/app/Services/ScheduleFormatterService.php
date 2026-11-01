<?php

namespace App\Services;

use App\Models\RestaurantSchedule;
use Carbon\Carbon;

/**
 * Presentation helpers for structured weekly shifts.
 *
 * The database stores only rows (day_of_week, open_time, close_time,
 * spans_next_day). Everything here is derived on the fly and never persisted.
 */
class ScheduleFormatterService
{
    /**
     * Compact weekly summary for cards and tables.
     *
     * @param  iterable<int, RestaurantSchedule|array<string, mixed>>  $schedules
     */
    public function summary(iterable $schedules): string
    {
        $byDay = $this->groupByDay($schedules);
        $groups = $this->groupDaysBySignature($byDay);

        $openGroups = array_values(array_filter(
            $groups,
            fn (array $group) => $group['signature'] !== 'closed'
        ));

        if ($openGroups === []) {
            return 'Hours not published';
        }

        if (count($groups) === 1 && $groups[0]['signature'] !== 'closed' && count($groups[0]['days']) === 7) {
            return 'Daily ' . $this->timesLabel($groups[0]['shifts']);
        }

        $parts = [];
        foreach ($groups as $group) {
            $days = $this->dayLabel($group['days']);
            $parts[] = $group['signature'] === 'closed'
                ? "{$days} closed"
                : "{$days} " . $this->timesLabel($group['shifts']);
        }

        return implode(' · ', $parts);
    }

    /**
     * Seven entries, Monday (1) through Sunday (7), ready for the API.
     *
     * @param  iterable<int, RestaurantSchedule|array<string, mixed>>  $schedules
     * @return array<int, array<string, mixed>>
     */
    public function weeklyHours(iterable $schedules): array
    {
        $byDay = $this->groupByDay($schedules);
        $week = [];

        foreach ($byDay as $day => $shifts) {
            $isClosed = $shifts === [];

            $week[] = [
                'day_of_week'    => $day,
                'day_name'       => RestaurantSchedule::DAY_NAMES[$day],
                'day_name_short' => RestaurantSchedule::DAY_NAMES_SHORT[$day],
                'is_closed'      => $isClosed,
                'shift_count'    => count($shifts),
                'summary'        => $isClosed ? 'Closed' : $this->timesLabel($shifts),
                'shifts'         => array_map(
                    fn (array $shift) => [
                        'open_time'      => $shift['open'],
                        'close_time'     => $shift['close'],
                        'spans_next_day' => $shift['spans'],
                        'service_label'  => $this->serviceLabel($day, $shift['open'], $shift['close'], $shift['spans']),
                        'formatted_time' => $this->timeRangeLabel($shift),
                    ],
                    $shifts
                ),
            ];
        }

        return $week;
    }

    /**
     * @param  iterable<int, RestaurantSchedule|array<string, mixed>>  $schedules
     * @return array<int, array<int, array{open: string, close: string, spans: bool}>>
     */
    private function groupByDay(iterable $schedules): array
    {
        $byDay = array_fill(1, 7, []);

        foreach ($schedules as $schedule) {
            $shift = $this->normalizeShift($schedule);
            $byDay[$shift['day']][] = [
                'open'  => $shift['open'],
                'close' => $shift['close'],
                'spans' => $shift['spans'],
            ];
        }

        foreach ($byDay as $day => $shifts) {
            usort($shifts, fn (array $a, array $b) => [$a['open'], $a['close']] <=> [$b['open'], $b['close']]);
            $byDay[$day] = $shifts;
        }

        return $byDay;
    }

    /**
     * @param  RestaurantSchedule|array<string, mixed>  $schedule
     * @return array{day: int, open: string, close: string, spans: bool}
     */
    private function normalizeShift(RestaurantSchedule|array $schedule): array
    {
        $data = $schedule instanceof RestaurantSchedule ? $schedule->getAttributes() : $schedule;

        $open = substr((string) $data['open_time'], 0, 5);
        $close = substr((string) $data['close_time'], 0, 5);

        return [
            'day'   => (int) $data['day_of_week'],
            'open'  => $open,
            'close' => $close,
            'spans' => array_key_exists('spans_next_day', $data)
                ? filter_var($data['spans_next_day'], FILTER_VALIDATE_BOOLEAN)
                : $close < $open,
        ];
    }

    /**
     * @param  array<int, array<int, array{open: string, close: string, spans: bool}>>  $byDay
     * @return array<int, array{days: array<int>, shifts: array<int, array<string, mixed>>, signature: string}>
     */
    private function groupDaysBySignature(array $byDay): array
    {
        $signatures = [];
        foreach ($byDay as $day => $shifts) {
            $signatures[$day] = $shifts === []
                ? 'closed'
                : implode(';', array_map(
                    fn (array $shift) => "{$shift['open']}-{$shift['close']}-" . (int) $shift['spans'],
                    $shifts
                ));
        }

        // Group every day with the same service pattern, not only consecutive
        // days, so "Mon-Thu, Sun" stays one readable group. Closed days are
        // reported after the open groups.
        $grouped = [];
        foreach ($signatures as $day => $signature) {
            $grouped[$signature][] = $day;
        }

        $groups = [];
        foreach ($grouped as $signature => $days) {
            sort($days);
            $groups[] = [
                'days'      => $days,
                'signature' => $signature,
                'shifts'    => $byDay[$days[0]],
            ];
        }

        usort($groups, function (array $a, array $b) {
            if ($a['signature'] === 'closed' || $b['signature'] === 'closed') {
                return ($a['signature'] === 'closed') <=> ($b['signature'] === 'closed');
            }

            return $a['days'][0] <=> $b['days'][0];
        });

        return $groups;
    }

    /**
     * @param  array<int, int>  $days
     */
    private function dayLabel(array $days): string
    {
        sort($days);

        $ranges = [];
        $start = null;
        $previous = null;

        foreach ($days as $day) {
            if ($start === null) {
                $start = $previous = $day;
                continue;
            }

            if ($day === $previous + 1) {
                $previous = $day;
                continue;
            }

            $ranges[] = [$start, $previous];
            $start = $previous = $day;
        }

        if ($start !== null) {
            $ranges[] = [$start, $previous];
        }

        return implode(', ', array_map(function (array $range) {
            [$from, $to] = $range;

            if ($from === $to) {
                return RestaurantSchedule::DAY_NAMES_SHORT[$from];
            }

            return RestaurantSchedule::DAY_NAMES_SHORT[$from]
                . '–'
                . RestaurantSchedule::DAY_NAMES_SHORT[$to];
        }, $ranges));
    }

    /**
     * @param  array<int, array{open: string, close: string, spans: bool}>  $shifts
     */
    private function timesLabel(array $shifts): string
    {
        return implode(', ', array_map(
            fn (array $shift) => $this->timeRangeLabel($shift),
            $shifts
        ));
    }

    /**
     * @param  array{open: string, close: string, spans: bool}  $shift
     */
    private function timeRangeLabel(array $shift): string
    {
        $open = $this->clock($shift['open']);
        $close = $this->clock($shift['close']);

        return $open . ' – ' . $close . ($shift['spans'] ? ' (+1)' : '');
    }

    private function clock(string $time): string
    {
        $formatted = Carbon::createFromFormat('H:i', $time)->format('g:i a');

        // "9:00 pm" reads better as "9 pm"; half hours keep their minutes.
        return str_replace(':00 ', ' ', $formatted);
    }

    /**
     * Human meal label(s) covered by one continuous service window.
     *
     * A single row can cover several meals, e.g. 11:00-22:00 reads as
     * "Lunch & dinner" — the window stays one row in the database.
     */
    private function serviceLabel(int $day, string $open, string $close, bool $spans): string
    {
        $openMinutes = $this->minutes($open);
        $closeMinutes = $this->minutes($close) + ($spans ? 1440 : 0);

        // A window that opens early and ends by mid-afternoon is a breakfast
        // or brunch service; it never reaches dinner.
        if ($openMinutes < 10 * 60 && $closeMinutes <= 16 * 60) {
            return $day >= 6 ? 'Brunch' : 'Breakfast';
        }

        $labels = [];

        if ($openMinutes < 10 * 60) {
            $labels[] = $day >= 6 ? 'Brunch' : 'Breakfast';
        }

        if ($this->overlaps($openMinutes, $closeMinutes, 11 * 60, 15 * 60)) {
            $labels[] = 'Lunch';
        }

        if ($this->overlaps($openMinutes, $closeMinutes, 17 * 60, 22 * 60)) {
            $labels[] = 'Dinner';
        }

        // Anything past 11 pm (or crossing midnight) is late-night service.
        if ($spans || $closeMinutes > 23 * 60) {
            $labels[] = 'Late night';
        }

        if ($labels === []) {
            return 'Service';
        }

        return $this->joinLabels($labels);
    }

    /**
     * Sentence-case join: "Lunch & dinner", "Lunch, dinner & late night".
     *
     * @param  array<int, string>  $labels
     */
    private function joinLabels(array $labels): string
    {
        $first = array_shift($labels);
        $rest = array_map('mb_strtolower', $labels);

        if ($rest === []) {
            return $first;
        }

        $last = array_pop($rest);

        return $rest === []
            ? $first . ' & ' . $last
            : $first . ', ' . implode(', ', $rest) . ' & ' . $last;
    }

    private function minutes(string $time): int
    {
        [$hours, $minutes] = array_map('intval', explode(':', substr($time, 0, 5)));

        return $hours * 60 + $minutes;
    }

    private function overlaps(int $start, int $end, int $windowStart, int $windowEnd): bool
    {
        return $start < $windowEnd && $end > $windowStart;
    }
}
