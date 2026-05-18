# Pulse Cronwatch

A [Laravel Pulse](https://pulse.laravel.com) card and recorder that monitors your scheduled commands (cron jobs): success / failure / skipped counts, last run timestamp, and average / max duration over the selected Pulse period.

[![Latest Stable Version](https://poser.pugx.org/kalimulhaq/pulse-cronwatch/v)](https://packagist.org/packages/kalimulhaq/pulse-cronwatch)
[![License](https://poser.pugx.org/kalimulhaq/pulse-cronwatch/license)](https://github.com/kalimulhaq/pulse-cronwatch/blob/main/LICENSE.md)

## What it does

Pulse Cronwatch listens for Laravel's scheduled-task lifecycle events:

- `Illuminate\Console\Events\ScheduledTaskFinished`
- `Illuminate\Console\Events\ScheduledTaskFailed`
- `Illuminate\Console\Events\ScheduledTaskSkipped`

and writes them into Pulse's storage as four entries per command:

| Pulse type | Aggregates | Purpose |
|---|---|---|
| `schedule_success` | count, max, avg | Successful runs and their durations (ms) |
| `schedule_failed` | count | Failed runs |
| `schedule_skipped` | count | Runs skipped by the scheduler (overlap protection, `when()` callbacks, etc.) |
| `schedule_last_run` (Pulse value) | — | ISO-8601 timestamp of the most recent terminal event |

The companion Livewire card joins those four streams per command and renders a table inside your Pulse dashboard, sortable by failure count, success count, average duration, or last run.

## Requirements

- PHP `^8.2` or `^8.3`
- Laravel `^11.0`, `^12.0`, or `^13.0`
- Laravel Pulse `^1.4`
- Livewire `^3.0`

## Installation

```bash
composer require kalimulhaq/pulse-cronwatch
```

The package auto-registers its service provider.

### 1. Register the recorder

Add the recorder to your `config/pulse.php` under `recorders`:

```php
'recorders' => [
    \Kalimulhaq\PulseCronwatch\Recorders\Schedule::class => [],
    // ...your existing recorders
],
```

(No per-recorder options are required for the initial release.)

### 2. Add the card to your Pulse dashboard

If you have not already done so, publish Pulse's dashboard view:

```bash
php artisan vendor:publish --tag=pulse-dashboard
```

Then add the card to `resources/views/vendor/pulse/dashboard.blade.php` wherever it fits in your grid:

```blade
<livewire:cronwatch.schedule cols="8" />
```

The `cols` attribute accepts any Pulse column value (`1`–`12` or `full`); `rows` is also supported the same way other Pulse cards expose it.

### 3. Run your scheduler

Once `php artisan schedule:work` (locally) or the system cron (in production) starts firing your scheduled tasks, runs will appear on the card immediately. Pulse's period selector (1h / 6h / 24h / 7d) applies to the card.

## What you see

For each command that has either fired or is currently scheduled, the card displays:

- **Command** — the scheduler's summary line (e.g. `'artisan' 'app:sync-properties'`, or the description you set via `->name('…')`)
- **Last run** — relative time of the most recent terminal event (Pulse value, separate from the period filter)
- **Avg** — average duration in milliseconds across successful runs in the selected period
- **Success** — successful runs in the selected period
- **Failed** — failed runs in the selected period (highlighted red when > 0)
- **Skipped** — runs skipped by the scheduler

## Caveats

- **Currently displays only commands that have actually fired.** A command that is scheduled but has never run in the selected period (because the period is short, or it's freshly added) will not appear. Listing scheduled-but-never-fired commands from `app(Schedule::class)->events()` is on the roadmap.
- **Command names are the scheduler's display summary.** If you rename a command or change its arguments, the new signature is treated as a different row. Use `->name('stable-name')` on the scheduled task if you want a stable label.
- **Pulse storage trimming.** Pulse auto-trims old entries per `pulse.storage.trim.keep`. Cronwatch entries follow the same retention. Historical data older than that window won't appear on the card.

## Testing

```bash
composer install
vendor/bin/phpunit
```

The test suite uses `orchestra/testbench` and an in-memory SQLite database with the real Pulse migrations.

## Contributing

See [CONTRIBUTING.md](CONTRIBUTING.md). Bug reports, suggestions, and pull requests are welcome.

## License

MIT — see [LICENSE.md](LICENSE.md).
