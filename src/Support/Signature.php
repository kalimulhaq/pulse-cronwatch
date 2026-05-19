<?php

namespace Kalimulhaq\PulseCronwatch\Support;

class Signature
{
    /**
     * Strip the PHP/artisan shell wrapping from a scheduled task's signature.
     *
     *   "'/usr/bin/php8.3' 'artisan' migration:developers-reelly > '/dev/null' 2>&1"
     *     → "migration:developers-reelly"
     *
     * Closures, ->name('...')-labelled tasks, and anything that doesn't match
     * the shell-wrapped pattern pass through unchanged.
     */
    public static function normalize(string $signature): string
    {
        $pattern = "/^\s*'[^']*php[^']*'\s+'artisan'\s+(.+?)(?:\s+(?:>|2>|&>|>>)\s*\S.*)?$/";

        if (preg_match($pattern, $signature, $m)) {
            return trim($m[1]);
        }

        return $signature;
    }
}
