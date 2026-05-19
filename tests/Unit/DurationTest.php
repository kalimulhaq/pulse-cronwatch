<?php

namespace Kalimulhaq\PulseCronwatch\Tests\Unit;

use Kalimulhaq\PulseCronwatch\Support\Duration;
use PHPUnit\Framework\TestCase;

class DurationTest extends TestCase
{
    public function test_zero_renders_as_zero_ms(): void
    {
        $this->assertSame('0ms', Duration::format(0));
    }

    public function test_sub_second_renders_in_ms_with_thousands_separator(): void
    {
        $this->assertSame('412ms', Duration::format(412));
        $this->assertSame('999ms', Duration::format(999));
    }

    public function test_one_second_boundary(): void
    {
        $this->assertSame('1s', Duration::format(1_000));
    }

    public function test_seconds_show_one_decimal_below_ten(): void
    {
        $this->assertSame('5.1s', Duration::format(5_100));
        $this->assertSame('1.4s', Duration::format(1_400));
    }

    public function test_seconds_drop_decimal_at_ten_or_more(): void
    {
        $this->assertSame('12s', Duration::format(12_000));
        $this->assertSame('59s', Duration::format(59_400));
    }

    public function test_one_minute_boundary(): void
    {
        $this->assertSame('1m', Duration::format(60_000));
    }

    public function test_minutes_show_one_decimal_below_ten(): void
    {
        $this->assertSame('2.5m', Duration::format(150_000));
    }

    public function test_minutes_drop_decimal_at_ten_or_more(): void
    {
        $this->assertSame('30m', Duration::format(1_800_000));
    }

    public function test_one_hour_boundary(): void
    {
        $this->assertSame('1h', Duration::format(3_600_000));
    }

    public function test_hours_show_one_decimal_below_ten(): void
    {
        // 4,098,800 ms = 1.13 h → rounds to 1.1
        $this->assertSame('1.1h', Duration::format(4_098_800));
        // 12,345,678 ms = 3.43 h → rounds to 3.4
        $this->assertSame('3.4h', Duration::format(12_345_678));
    }
}
