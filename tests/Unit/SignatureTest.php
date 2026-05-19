<?php

namespace Kalimulhaq\PulseCronwatch\Tests\Unit;

use Kalimulhaq\PulseCronwatch\Support\Signature;
use PHPUnit\Framework\TestCase;

class SignatureTest extends TestCase
{
    public function test_strips_full_shell_wrapping_with_path_and_redirects(): void
    {
        $input = "'/usr/bin/php8.3' 'artisan' migration:developers-reelly > '/dev/null' 2>&1";

        $this->assertSame('migration:developers-reelly', Signature::normalize($input));
    }

    public function test_strips_php_without_path(): void
    {
        $input = "'php' 'artisan' foo:bar";

        $this->assertSame('foo:bar', Signature::normalize($input));
    }

    public function test_strips_trailing_redirect_to_devnull(): void
    {
        $input = "'/usr/bin/php' 'artisan' app:hourly-sync > /dev/null 2>&1";

        $this->assertSame('app:hourly-sync', Signature::normalize($input));
    }

    public function test_preserves_arguments_after_command(): void
    {
        $input = "'/usr/bin/php' 'artisan' migration:projects-reelly --context=cron --duration=\"Last 24 Hour\" > '/dev/null' 2>&1";

        $this->assertSame(
            'migration:projects-reelly --context=cron --duration="Last 24 Hour"',
            Signature::normalize($input)
        );
    }

    public function test_passes_through_closure_label_unchanged(): void
    {
        $input = 'Closure at /var/www/app/routes/console.php:12';

        $this->assertSame($input, Signature::normalize($input));
    }

    public function test_passes_through_named_task_unchanged(): void
    {
        $input = 'send-emails-nightly';

        $this->assertSame($input, Signature::normalize($input));
    }

    public function test_passes_through_empty_string(): void
    {
        $this->assertSame('', Signature::normalize(''));
    }

    public function test_passes_through_string_that_does_not_match_shell_pattern(): void
    {
        $input = 'some weird thing with quotes\' inside it';

        $this->assertSame($input, Signature::normalize($input));
    }
}
