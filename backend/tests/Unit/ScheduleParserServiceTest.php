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

    public function test_parses_12_pm_noon_and_half_past_noon(): void
    {
        // "12 pm" is noon, not midnight — John's Grill Sunday opens at 12 pm.
        $noon = $this->parser->parseTimeRange('12 pm - 10 pm');
        $this->assertSame('12:00:00', $noon[0]);
        $this->assertSame('22:00:00', $noon[1]);
        $this->assertFalse($noon[2]);

        // "12:15 pm" is 12:15 in the afternoon, not 00:15.
        $quarter = $this->parser->parseTimeRange('12:15 pm - 12:15 pm');
        $this->assertSame('12:15:00', $quarter[0]);
        $this->assertSame('12:15:00', $quarter[1]);

        // And 12:30 am stays just after midnight.
        $late = $this->parser->parseTimeRange('11 am - 12:30 am');
        $this->assertSame('00:30:00', $late[1]);
        $this->assertTrue($late[2]);
    }

    public function test_parses_complex_parallel_37_schedule(): void
    {
        $raw = "Mon, Fri 5 pm - 6:15 pm / Tues 12:15 pm - 12:15 pm / Weds 1:15 pm - 5:45 pm / Thurs, Sat 10 am - 3 pm / Sun 6:30 am - 12:45 pm";
        $shifts = $this->parser->parse($raw);

        // Mon, Fri = 2
        // Tues = zero-length window, treated as closed and dropped
        // Weds = 1
        // Thurs, Sat = 2
        // Sun = 1
        // Total = 6 shifts
        $this->assertCount(6, $shifts);
    }

    public function test_parses_split_service_with_ampersand(): void
    {
        $shifts = $this->parser->parse('Mon-Fri 11:30 am - 2:30 pm & 5 pm - 10 pm');

        $this->assertCount(10, $shifts);

        $monday = array_values(array_filter(
            $shifts,
            fn (array $shift) => $shift['day_of_week'] === 1
        ));

        $this->assertCount(2, $monday);
        $this->assertSame('11:30:00', $monday[0]['open_time']);
        $this->assertSame('14:30:00', $monday[0]['close_time']);
        $this->assertSame('17:00:00', $monday[1]['open_time']);
        $this->assertSame('22:00:00', $monday[1]['close_time']);
        $this->assertFalse($monday[0]['spans_next_day']);
    }

    public function test_parses_split_service_with_semicolon_and_plus(): void
    {
        $this->assertCount(14, $this->parser->parse('Mon-Sun 11 am - 3 pm ; 5 pm - 9 pm'));
        $this->assertCount(14, $this->parser->parse('Mon-Sun 11 am - 3 pm + 5 pm - 9 pm'));
    }

    public function test_parses_split_service_that_crosses_midnight(): void
    {
        $shifts = $this->parser->parse('Mon-Sun 11 am - 3 pm & 5 pm - 4 am');
        $late = array_values(array_filter(
            $shifts,
            fn (array $shift) => $shift['open_time'] === '17:00:00'
        ));

        $this->assertCount(7, $late);
        $this->assertTrue($late[0]['spans_next_day']);
        $this->assertSame('04:00:00', $late[0]['close_time']);
    }

    public function test_overlapping_sections_do_not_duplicate_identical_shifts(): void
    {
        $shifts = $this->parser->parse('Mon-Sun 11 am - 10 pm / Mon 11 am - 10 pm');

        $this->assertCount(7, $shifts);
    }

    public function test_rejects_unknown_service_separator_content(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->parser->parse('Mon-Sun 11 am - 10 pm & later');
    }
}
