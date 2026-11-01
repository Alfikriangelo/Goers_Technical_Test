<?php

namespace Tests\Unit;

use App\Services\ScheduleFormatterService;
use PHPUnit\Framework\TestCase;

class ScheduleFormatterServiceTest extends TestCase
{
    private ScheduleFormatterService $formatter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->formatter = new ScheduleFormatterService();
    }

    public function test_summary_groups_days_and_marks_closed_days(): void
    {
        $rows = [];
        foreach (range(1, 6) as $day) {
            $rows[] = ['day_of_week' => $day, 'open_time' => '11:00:00', 'close_time' => '22:00:00', 'spans_next_day' => false];
        }

        $this->assertSame(
            'Mon–Sat 11 am – 10 pm · Sun closed',
            $this->formatter->summary($rows)
        );
    }

    public function test_summary_uses_daily_when_every_day_matches(): void
    {
        $rows = [];
        foreach (range(1, 7) as $day) {
            $rows[] = ['day_of_week' => $day, 'open_time' => '17:30:00', 'close_time' => '02:00:00', 'spans_next_day' => true];
        }

        $this->assertSame('Daily 5:30 pm – 2 am (+1)', $this->formatter->summary($rows));
    }

    public function test_weekly_hours_include_service_labels_and_closed_days(): void
    {
        $rows = [
            ['day_of_week' => 1, 'open_time' => '11:00:00', 'close_time' => '15:00:00', 'spans_next_day' => false],
            ['day_of_week' => 1, 'open_time' => '17:00:00', 'close_time' => '02:00:00', 'spans_next_day' => true],
        ];

        $week = $this->formatter->weeklyHours($rows);

        $this->assertCount(7, $week);
        $this->assertSame('Monday', $week[0]['day_name']);
        $this->assertFalse($week[0]['is_closed']);
        $this->assertCount(2, $week[0]['shifts']);
        $this->assertSame('Lunch', $week[0]['shifts'][0]['service_label']);
        $this->assertSame('Dinner & late night', $week[0]['shifts'][1]['service_label']);
        $this->assertSame('5 pm – 2 am (+1)', $week[0]['shifts'][1]['formatted_time']);
        $this->assertTrue($week[1]['is_closed']);
        $this->assertSame('Closed', $week[1]['summary']);
    }

    public function test_continuous_window_is_labelled_with_every_meal_it_covers(): void
    {
        $rows = [
            ['day_of_week' => 1, 'open_time' => '11:00:00', 'close_time' => '22:00:00', 'spans_next_day' => false],
            ['day_of_week' => 2, 'open_time' => '11:00:00', 'close_time' => '04:00:00', 'spans_next_day' => true],
        ];

        $week = $this->formatter->weeklyHours($rows);

        // One row, one shift — the label still says both meals are served.
        $this->assertCount(1, $week[0]['shifts']);
        $this->assertSame('Lunch & dinner', $week[0]['shifts'][0]['service_label']);
        $this->assertSame('Lunch, dinner & late night', $week[1]['shifts'][0]['service_label']);
    }
}
