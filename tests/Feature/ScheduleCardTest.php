<?php

namespace Kalimulhaq\PulseCronwatch\Tests\Feature;

use Carbon\CarbonImmutable;
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
}
