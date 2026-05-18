<?php

namespace Kalimulhaq\PulseCronwatch\Tests\Feature;

use Carbon\CarbonImmutable;
use Illuminate\Console\Scheduling\Schedule as LaravelSchedule;
use Kalimulhaq\PulseCronwatch\Livewire\Schedule;
use Kalimulhaq\PulseCronwatch\Tests\TestCase;
use Laravel\Pulse\Facades\Pulse;
use Livewire\Livewire;

class ScheduleCardTest extends TestCase
{
    public function test_card_renders_with_no_data(): void
    {
        Livewire::withoutLazyLoading()
            ->test(Schedule::class)
            ->assertOk()
            ->assertSee('Scheduled Commands');
    }

    public function test_card_renders_rows_from_recorded_data(): void
    {
        $now = CarbonImmutable::now();

        Pulse::record('schedule_success', 'app:sync-properties', 1500, $now)->count()->max()->avg();
        Pulse::record('schedule_failed', 'app:sync-properties', 0, $now)->count();
        Pulse::set('schedule_last_run', 'app:sync-properties', $now->toIso8601String(), $now);
        Pulse::ingest();

        Livewire::withoutLazyLoading()
            ->test(Schedule::class)
            ->assertOk()
            ->assertSee('app:sync-properties');
    }

    public function test_card_shows_scheduled_command_that_has_never_run(): void
    {
        $this->registerScheduledCommand('app:never-run-yet', '0 3 * * *');

        Livewire::withoutLazyLoading()
            ->test(Schedule::class)
            ->assertOk()
            ->assertSee('app:never-run-yet')
            ->assertSee('0 3 * * *')
            ->assertSee('Never');
    }

    public function test_card_shows_cron_expression_alongside_recorded_command(): void
    {
        $this->registerScheduledCommand('app:hourly-sync', '0 * * * *');

        $now = CarbonImmutable::now();
        Pulse::record('schedule_success', 'app:hourly-sync', 500, $now)->count()->max()->avg();
        Pulse::set('schedule_last_run', 'app:hourly-sync', $now->toIso8601String(), $now);
        Pulse::ingest();

        Livewire::withoutLazyLoading()
            ->test(Schedule::class)
            ->assertOk()
            ->assertSee('app:hourly-sync')
            ->assertSee('0 * * * *');
    }

    public function test_sorting_by_next_due_puts_soonest_first(): void
    {
        $this->registerScheduledCommand('app:soon', '0 * * * *');   // hourly  → next due ≤ 60 min away
        $this->registerScheduledCommand('app:later', '0 0 1 1 *');  // once a year on Jan 1 → up to 12 months away

        Livewire::withoutLazyLoading()
            ->test(Schedule::class)
            ->assertOk()
            ->assertSeeInOrder(['app:soon', 'app:later']);
    }

    /**
     * Register a console command in Laravel's Schedule registry so the
     * card sees it via app(Schedule::class)->events().
     */
    protected function registerScheduledCommand(string $signature, string $expression): void
    {
        /** @var LaravelSchedule $schedule */
        $schedule = $this->app->make(LaravelSchedule::class);
        $schedule->command($signature)->cron($expression);
    }
}
