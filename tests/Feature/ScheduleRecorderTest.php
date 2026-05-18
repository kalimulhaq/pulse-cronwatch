<?php

namespace Kalimulhaq\PulseCronwatch\Tests\Feature;

use Illuminate\Console\Events\ScheduledTaskFailed;
use Illuminate\Console\Events\ScheduledTaskFinished;
use Illuminate\Console\Events\ScheduledTaskSkipped;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Support\Facades\DB;
use Kalimulhaq\PulseCronwatch\Recorders\Schedule;
use Kalimulhaq\PulseCronwatch\Tests\TestCase;
use Laravel\Pulse\Facades\Pulse;
use RuntimeException;

class ScheduleRecorderTest extends TestCase
{
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('pulse.recorders', [
            Schedule::class => [],
        ]);
    }

    public function test_records_a_finished_task_as_success_with_duration(): void
    {
        $event = new ScheduledTaskFinished($this->makeTaskEvent('artisan-command:run'), runtime: 1.234);

        event($event);
        Pulse::ingest();

        $rows = DB::table('pulse_entries')->where('type', 'schedule_success')->get();
        $this->assertCount(1, $rows);
        $this->assertSame('artisan-command:run', $rows[0]->key);
        $this->assertSame(1234, (int) $rows[0]->value);
    }

    public function test_records_a_failed_task_as_failure(): void
    {
        $event = new ScheduledTaskFailed(
            $this->makeTaskEvent('artisan-command:run'),
            new RuntimeException('boom'),
        );

        event($event);
        Pulse::ingest();

        $rows = DB::table('pulse_entries')->where('type', 'schedule_failed')->get();
        $this->assertCount(1, $rows);
        $this->assertSame('artisan-command:run', $rows[0]->key);
    }

    public function test_records_a_skipped_task(): void
    {
        $event = new ScheduledTaskSkipped($this->makeTaskEvent('artisan-command:run'));

        event($event);
        Pulse::ingest();

        $rows = DB::table('pulse_entries')->where('type', 'schedule_skipped')->get();
        $this->assertCount(1, $rows);
        $this->assertSame('artisan-command:run', $rows[0]->key);
    }

    public function test_writes_last_run_timestamp_on_each_terminal_event(): void
    {
        $event = new ScheduledTaskFinished($this->makeTaskEvent('artisan-command:run'), runtime: 0.5);

        event($event);
        Pulse::ingest();

        $row = DB::table('pulse_values')
            ->where('type', 'schedule_last_run')
            ->where('key', 'artisan-command:run')
            ->first();

        $this->assertNotNull($row);
        $this->assertNotEmpty($row->value);
    }

    protected function makeTaskEvent(string $command): Event
    {
        $task = new Event(
            mutex: new \Illuminate\Console\Scheduling\CacheEventMutex($this->app['cache']),
            command: $command,
        );

        if ($command !== '') {
            $task->description = $command;
        }

        return $task;
    }
}
