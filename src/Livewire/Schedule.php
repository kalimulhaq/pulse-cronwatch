<?php

namespace Kalimulhaq\PulseCronwatch\Livewire;

use Illuminate\Console\Scheduling\Schedule as LaravelSchedule;
use Illuminate\Contracts\Console\Kernel as ConsoleKernel;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\View;
use Kalimulhaq\PulseCronwatch\Support\Signature;
use Laravel\Pulse\Livewire\Card;
use Livewire\Attributes\Lazy;
use Livewire\Attributes\Url;
use Throwable;

#[Lazy]
class Schedule extends Card
{
    /**
     * Column to sort the table by.
     *
     * @var 'last_run'|'failed'|'avg_duration'|'success'|'next_due'
     */
    #[Url(as: 'cronwatch-order-by')]
    public string $orderBy = 'next_due';

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
     * Build a single collection of rows, one per scheduled command, joining
     * Laravel's live Schedule registry (for cron + next due) with recorded
     * Pulse data (for counts, durations, last run).
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

        $scheduled = $this->scheduledEvents();

        $keys = $counts->keys()
            ->merge($durations->keys())
            ->merge($lastRuns->keys())
            ->merge($scheduled->keys())
            ->unique();

        $rows = $keys->map(function (string $key) use ($counts, $durations, $lastRuns, $scheduled) {
            $count = $counts->get($key);
            $duration = $durations->get($key);
            $info = $scheduled->get($key);

            return (object) [
                'command' => $key,
                'cron' => $info['cron'] ?? null,
                'next_due' => $info['next_due'] ?? null,
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
     * Snapshot of Laravel's scheduled events keyed by signature, each value
     * containing the cron expression and next due timestamp (ISO 8601).
     *
     * Wrapped in try/catch so a malformed scheduler entry can't take down the
     * whole card — it just means that one row falls back to recorder-only data.
     *
     * @return Collection<string, array{cron: ?string, next_due: ?string}>
     */
    protected function scheduledEvents(): Collection
    {
        try {
            // The Schedule singleton is bound by the Console Kernel's
            // defineConsoleSchedule() callback. Web requests construct the
            // HTTP Kernel only, so resolving the Console Kernel here triggers
            // the binding that populates events from App\Console\Kernel::schedule()
            // and/or routes/console.php on first access of LaravelSchedule.
            app(ConsoleKernel::class);
            $events = app(LaravelSchedule::class)->events();
        } catch (Throwable) {
            return collect();
        }

        return collect($events)->mapWithKeys(function ($event) {
            $signature = $event->getSummaryForDisplay();

            if (! is_string($signature) || trim($signature) === '') {
                return [];
            }

            $signature = Signature::normalize($signature);

            try {
                $nextDue = $event->nextRunDate()->format(\DateTimeInterface::ATOM);
            } catch (Throwable) {
                $nextDue = null;
            }

            return [$signature => [
                'cron' => method_exists($event, 'getExpression') ? $event->getExpression() : null,
                'next_due' => $nextDue,
            ]];
        });
    }

    /**
     * Sort the rows by the current orderBy column.
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
            'failed' => $rows->sortByDesc('failed'),
            // 'next_due' ascending: soonest first; null next_due (removed commands) goes last.
            default => $rows->sortBy(fn ($row) => $row->next_due ?? '9999-12-31T23:59:59+00:00'),
        };

        return $sorted->values();
    }
}
