<?php

namespace Kalimulhaq\PulseCronwatch\Recorders;

use Carbon\CarbonImmutable;
use Illuminate\Console\Events\ScheduledTaskFailed;
use Illuminate\Console\Events\ScheduledTaskFinished;
use Illuminate\Console\Events\ScheduledTaskSkipped;
use Illuminate\Console\Scheduling\Event;
use Laravel\Pulse\Pulse;

/**
 * Records Laravel scheduled task lifecycle events into Pulse storage.
 *
 * Three types are written, one per terminal outcome:
 *   - schedule_success  (count, max, avg over runtime in ms)
 *   - schedule_failed   (count)
 *   - schedule_skipped  (count)
 *
 * Plus one Pulse "value" per command for the last run timestamp:
 *   - schedule_last_run  (ISO-8601 string)
 */
class Schedule
{
    /**
     * The events this recorder subscribes to.
     *
     * @var list<class-string>
     */
    public array $listen = [
        ScheduledTaskFinished::class,
        ScheduledTaskFailed::class,
        ScheduledTaskSkipped::class,
    ];

    public function __construct(
        protected Pulse $pulse,
    ) {
    }

    public function record(ScheduledTaskFinished|ScheduledTaskFailed|ScheduledTaskSkipped $event): void
    {
        $signature = $this->signature($event->task);

        if ($signature === null) {
            return;
        }

        $now = CarbonImmutable::now();

        $this->pulse->lazy(function () use ($event, $signature, $now) {
            match (true) {
                $event instanceof ScheduledTaskFinished => $this->recordFinished($event, $signature, $now),
                $event instanceof ScheduledTaskFailed => $this->recordFailed($signature, $now),
                $event instanceof ScheduledTaskSkipped => $this->recordSkipped($signature, $now),
            };

            $this->pulse->set('schedule_last_run', $signature, $now->toIso8601String(), $now);
        });
    }

    protected function recordFinished(ScheduledTaskFinished $event, string $signature, CarbonImmutable $now): void
    {
        $durationMs = (int) round($event->runtime * 1000);

        $this->pulse->record(
            type: 'schedule_success',
            key: $signature,
            value: $durationMs,
            timestamp: $now,
        )->count()->max()->avg();
    }

    protected function recordFailed(string $signature, CarbonImmutable $now): void
    {
        $this->pulse->record(
            type: 'schedule_failed',
            key: $signature,
            value: 0,
            timestamp: $now,
        )->count();
    }

    protected function recordSkipped(string $signature, CarbonImmutable $now): void
    {
        $this->pulse->record(
            type: 'schedule_skipped',
            key: $signature,
            value: 0,
            timestamp: $now,
        )->count();
    }

    /**
     * Resolve a stable, human-readable signature for a scheduled task.
     */
    protected function signature(Event $task): ?string
    {
        $summary = $task->getSummaryForDisplay();

        if (! is_string($summary) || trim($summary) === '') {
            return null;
        }

        return $summary;
    }
}
