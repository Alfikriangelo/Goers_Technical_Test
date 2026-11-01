<?php

namespace App\Services;

use Carbon\Carbon;
use InvalidArgumentException;

class ScheduleParserService
{
    private const DAY_MAP = [
        'mon'       => 1,
        'monday'    => 1,
        'tue'       => 2,
        'tues'      => 2,
        'tuesday'   => 2,
        'wed'       => 3,
        'weds'      => 3,
        'wednesday' => 3,
        'thu'       => 4,
        'thurs'     => 4,
        'thursday'  => 4,
        'fri'       => 5,
        'friday'    => 5,
        'sat'       => 6,
        'saturday'  => 6,
        'sun'       => 7,
        'sunday'    => 7,
    ];

    /**
     * Parse a raw schedule string into structured array of shifts.
     *
     * Example input:
     * "Mon-Thu, Sun 11:30 am - 9 pm / Fri-Sat 11:30 am - 9:30 pm"
     * "Mon-Fri 11:30 am - 2:30 pm & 5 pm - 10 pm / Sat-Sun 10 am - 3 pm"
     *
     * A section may describe more than one service per day. Those services are
     * separated with "&", ";" or "+", so lunch and dinner can live on the same
     * weekday without inventing a second section for the same days.
     *
     * @param string $rawSchedule
     * @return array<int, array{day_of_week: int, open_time: string, close_time: string, spans_next_day: bool}>
     */
    public function parse(string $rawSchedule): array
    {
        $rawSchedule = trim($rawSchedule);
        if (empty($rawSchedule)) {
            return [];
        }

        // Clean up newlines or extra whitespaces
        $rawSchedule = preg_replace('/\s+/', ' ', $rawSchedule);

        // Sections are separated by '/'
        $sections = array_map('trim', explode('/', $rawSchedule));
        $allShifts = [];

        foreach ($sections as $section) {
            if (empty($section)) {
                continue;
            }

            $shifts = $this->parseSection($section);
            foreach ($shifts as $shift) {
                $allShifts[] = $shift;
            }
        }

        // Overlapping day groups (e.g. "Mon-Sun ... / Mon ...") must not create
        // duplicate rows for the same service.
        $unique = [];
        foreach ($allShifts as $shift) {
            $key = implode('|', [
                $shift['day_of_week'],
                $shift['open_time'],
                $shift['close_time'],
                (int) $shift['spans_next_day'],
            ]);
            $unique[$key] = $shift;
        }

        return array_values($unique);
    }

    /**
     * Parse an individual section like:
     * "Mon-Thu, Sun 11:30 am - 9 pm"
     * "Mon-Fri 11:30 am - 2:30 pm & 5 pm - 10 pm"
     * "Tues 12:15 pm - 12:15 pm"
     * "Mon, Wed-Sun 11 am - 10 pm"
     */
    public function parseSection(string $section): array
    {
        $section = trim($section);

        // Split into [days part] and [time ranges part]. The days part ends at
        // the first clock token, so the service separator logic below only has
        // to deal with times.
        if (!preg_match('/^(.*?)\s+(\d{1,2}(?::\d{2})?\s*(?:am|pm).*)$/i', $section, $matches)) {
            throw new InvalidArgumentException("Unable to parse section format: '{$section}'");
        }

        $daysPart = trim($matches[1]);
        $timesPart = trim($matches[2]);

        if ($daysPart === '') {
            throw new InvalidArgumentException("Missing days in section: '{$section}'");
        }

        $days = $this->parseDaysPart($daysPart);
        $timeRanges = preg_split('/\s*(?:&|;|\+)\s*/', $timesPart) ?: [];

        $results = [];
        foreach ($timeRanges as $timeRange) {
            $timeRange = trim($timeRange);

            if (!preg_match('/^\d{1,2}(?::\d{2})?\s*(?:am|pm)\s*-\s*\d{1,2}(?::\d{2})?\s*(?:am|pm)$/i', $timeRange)) {
                throw new InvalidArgumentException("Unable to parse time range: '{$timeRange}' in section '{$section}'");
            }

            [$openTime, $closeTime, $spansNextDay] = $this->parseTimeRange($timeRange);

            // A zero-length window (e.g. "12:15 pm - 12:15 pm") cannot match
            // any real dining time; the source uses it to mean "closed".
            if ($openTime === $closeTime) {
                continue;
            }

            foreach ($days as $day) {
                $results[] = [
                    'day_of_week'    => $day,
                    'open_time'      => $openTime,
                    'close_time'     => $closeTime,
                    'spans_next_day' => $spansNextDay,
                ];
            }
        }

        return $results;
    }

    /**
     * Parse days string e.g.:
     * "Mon-Thu, Sun"
     * "Mon, Wed-Sun"
     * "Thurs, Sat"
     * "Mon-Sun"
     *
     * @return array<int> list of unique day integers (1 to 7)
     */
    public function parseDaysPart(string $daysStr): array
    {
        $daysStr = trim($daysStr);
        $groups = array_map('trim', explode(',', $daysStr));
        $dayNumbers = [];

        foreach ($groups as $group) {
            if (empty($group)) {
                continue;
            }

            // Check if it's a range like Mon-Thu
            if (str_contains($group, '-')) {
                [$startStr, $endStr] = array_map('trim', explode('-', $group, 2));
                $start = $this->resolveDay($startStr);
                $end = $this->resolveDay($endStr);

                if ($start <= $end) {
                    for ($d = $start; $d <= $end; $d++) {
                        $dayNumbers[] = $d;
                    }
                } else {
                    // e.g. Fri-Mon (wraps around weekend)
                    for ($d = $start; $d <= 7; $d++) {
                        $dayNumbers[] = $d;
                    }
                    for ($d = 1; $d <= $end; $d++) {
                        $dayNumbers[] = $d;
                    }
                }
            } else {
                // Single day
                $dayNumbers[] = $this->resolveDay($group);
            }
        }

        return array_values(array_unique($dayNumbers));
    }

    /**
     * Parse time range e.g. "11:30 am - 9 pm", "11 am - 4 am", "11 am - 12 am", "5 pm - 12:30 am"
     *
     * @return array{0: string, 1: string, 2: bool} [open_time 'H:i:s', close_time 'H:i:s', spans_next_day bool]
     */
    public function parseTimeRange(string $timeRangeStr): array
    {
        [$openStr, $closeStr] = array_map('trim', explode('-', $timeRangeStr, 2));

        $openTime = $this->normalizeTime($openStr);
        $closeTime = $this->normalizeTime($closeStr);

        // Determine if shift spans past midnight
        // If close_time <= open_time (e.g. opens 11:00 am, closes 4:00 am next day, or closes 12 am / 00:00:00)
        // Note: 12:00 am becomes 00:00:00.
        $spansNextDay = ($closeTime <= $openTime);

        // Special check: If open and close are identical (e.g. Tues 12:15 pm - 12:15 pm), spansNextDay might be false (instant/placeholder)
        if ($openTime === $closeTime) {
            $spansNextDay = false;
        }

        return [$openTime, $closeTime, $spansNextDay];
    }

    /**
     * Convert strings like "11:30 am", "9 pm", "12 am", "12:30 am", "11 am" to "H:i:s"
     */
    public function normalizeTime(string $timeStr): string
    {
        $timeStr = trim(strtolower($timeStr));

        // Format could be "g:i a" or "g a"
        if (str_contains($timeStr, ':')) {
            $time = Carbon::createFromFormat('g:i a', $timeStr);
        } else {
            $time = Carbon::createFromFormat('g a', $timeStr);
        }

        if (!$time) {
            throw new InvalidArgumentException("Unable to parse time: '{$timeStr}'");
        }

        return $time->format('H:i:s');
    }

    /**
     * Map day alias to integer 1..7
     */
    private function resolveDay(string $dayName): int
    {
        $cleaned = strtolower(trim($dayName));
        if (!isset(self::DAY_MAP[$cleaned])) {
            throw new InvalidArgumentException("Unrecognized day name: '{$dayName}'");
        }
        return self::DAY_MAP[$cleaned];
    }
}
