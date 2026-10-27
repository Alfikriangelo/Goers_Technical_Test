<?php

namespace Tests\Unit;

use App\Services\ScheduleParserService;
use PHPUnit\Framework\TestCase;

class ScheduleParserServiceTest extends TestCase
{
    private ScheduleParserService $parser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->parser = new ScheduleParserService();
    }

    public function test_parses_simple_range(): void
    {
        $shifts = $this->parser->parse("Mon-Sun 11:30 am - 9 pm");
        $this->assertCount(7, $shifts);
        $this->assertEquals(1, $shifts[0]['day_of_week']);
        $this->assertEquals('11:30:00', $shifts[0]['open_time']);
        $this->assertEquals('21:00:00', $shifts[0]['close_time']);
        $this->assertFalse($shifts[0]['spans_next_day']);
    }

    public function test_parses_multiple_sections_with_comma_and_dash(): void
    {
        $shifts = $this->parser->parse("Mon-Thu, Sun 11:30 am - 9 pm / Fri-Sat 11:30 am - 9:30 pm");
        // Mon-Thu (4) + Sun (1) = 5 shifts in section 1
        // Fri-Sat (2) = 2 shifts in section 2
        // Total = 7 shifts
        $this->assertCount(7, $shifts);

        $daysSection1 = array_column(array_slice($shifts, 0, 5), 'day_of_week');
        $this->assertEquals([1, 2, 3, 4, 7], $daysSection1);
    }

    public function test_parses_midnight_rollover(): void
    {
        $shifts = $this->parser->parse("Mon-Sun 11 am - 4 am");
        $this->assertCount(7, $shifts);
        $this->assertEquals('11:00:00', $shifts[0]['open_time']);
        $this->assertEquals('04:00:00', $shifts[0]['close_time']);
        $this->assertTrue($shifts[0]['spans_next_day']);
    }

    public function test_parses_12_am_midnight(): void
    {
        $shifts = $this->parser->parse("Mon-Sun 11 am - 12 am");
        $this->assertCount(7, $shifts);
        $this->assertEquals('11:00:00', $shifts[0]['open_time']);
        $this->assertEquals('00:00:00', $shifts[0]['close_time']);
        $this->assertTrue($shifts[0]['spans_next_day']);
    }

    public function test_parses_complex_parallel_37_schedule(): void
    {
        $raw = "Mon, Fri 5 pm - 6:15 pm / Tues 12:15 pm - 12:15 pm / Weds 1:15 pm - 5:45 pm / Thurs, Sat 10 am - 3 pm / Sun 6:30 am - 12:45 pm";
        $shifts = $this->parser->parse($raw);

        // Mon, Fri = 2
        // Tues = 1
        // Weds = 1
        // Thurs, Sat = 2
        // Sun = 1
        // Total = 7 shifts
        $this->assertCount(7, $shifts);
    }
}
