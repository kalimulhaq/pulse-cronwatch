<?php

namespace Kalimulhaq\PulseCronwatch\Support;

class Duration
{
    /**
     * Format a millisecond value with an adaptive unit:
     *   < 1000ms        → "412ms"
     *   < 60 seconds    → "5.1s"
     *   < 60 minutes    → "2.5m"
     *   otherwise       → "1.1h"
     */
    public static function format(int $ms): string
    {
        if ($ms < 1_000) {
            return number_format($ms).'ms';
        }
        if ($ms < 60_000) {
            return self::round($ms / 1_000).'s';
        }
        if ($ms < 3_600_000) {
            return self::round($ms / 60_000).'m';
        }

        return self::round($ms / 3_600_000).'h';
    }

    private static function round(float $value): string
    {
        return $value >= 10
            ? (string) (int) round($value)
            : (string) round($value, 1);
    }
}
