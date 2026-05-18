<?php

namespace Kalimulhaq\PulseCronwatch\Livewire;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\View;
use Laravel\Pulse\Livewire\Card;
use Livewire\Attributes\Lazy;
use Livewire\Attributes\Url;

#[Lazy]
class Schedule extends Card
{
    /**
     * Column to sort the table by.
     *
     * @var 'last_run'|'failed'|'avg_duration'|'success'
     */
    #[Url(as: 'cronwatch-order-by')]
    public string $orderBy = 'failed';

    public function render(): Renderable
    {
        [$schedules, $time, $runAt] = $this->remember(
            fn () => $this->buildRows(),
            $this->orderBy,
        );

        return View::make('pulse-cronwatch::livewire.schedule', [
            'schedules' => $schedules,
            'time' => $time,
            'runAt' => $runAt,
        ]);
    }

    /**
     * Build a single collection of rows, one per scheduled command, with
     * success / failed / skipped counts, max + avg duration, and last run.
     *
     * @return Collection<int, object>
     */
    protected function buildRows(): Collection
    {
        $counts = $this->aggregateTypes(
            ['schedule_success', 'schedule_failed', 'schedule_skipped'],
            'count',
        )->keyBy('key');

        $durations = $this->aggregate(
            'schedule_success',
            ['max', 'avg'],
        )->keyBy('key');

        $lastRuns = $this->values('schedule_last_run')->mapWithKeys(
            fn ($value) => [$value->key => $value->value],
        );

        $keys = $counts->keys()
            ->merge($durations->keys())
            ->merge($lastRuns->keys())
            ->unique();

        $rows = $keys->map(function (string $key) use ($counts, $durations, $lastRuns) {
            $count = $counts->get($key);
            $duration = $durations->get($key);

            return (object) [
                'command' => $key,
                'success' => (int) ($count->schedule_success ?? 0),
                'failed' => (int) ($count->schedule_failed ?? 0),
                'skipped' => (int) ($count->schedule_skipped ?? 0),
                'max_duration' => isset($duration->max) ? (int) $duration->max : null,
                'avg_duration' => isset($duration->avg) ? (int) $duration->avg : null,
                'last_run' => $lastRuns->get($key),
            ];
        });

        return $this->sortRows($rows->values());
    }

    /**
     * Sort the rows in place by the current orderBy column.
     *
     * @param  Collection<int, object>  $rows
     * @return Collection<int, object>
     */
    protected function sortRows(Collection $rows): Collection
    {
        $sorted = match ($this->orderBy) {
            'success' => $rows->sortByDesc('success'),
            'avg_duration' => $rows->sortByDesc('avg_duration'),
            'last_run' => $rows->sortByDesc('last_run'),
            default => $rows->sortByDesc('failed'),
        };

        return $sorted->values();
    }
}
