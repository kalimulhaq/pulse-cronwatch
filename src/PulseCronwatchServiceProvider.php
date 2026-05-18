<?php

namespace Kalimulhaq\PulseCronwatch;

use Illuminate\Support\ServiceProvider;
use Kalimulhaq\PulseCronwatch\Livewire\Schedule as ScheduleCard;
use Livewire\Livewire;

class PulseCronwatchServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'pulse-cronwatch');

        if (class_exists(Livewire::class)) {
            Livewire::component('cronwatch.schedule', ScheduleCard::class);
        }
    }
}
