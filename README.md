# Laravel Pulse Cronwatch

[![Latest Version on Packagist](https://img.shields.io/packagist/v/kalimulhaq/pulse-cronwatch.svg?style=flat-square)](https://packagist.org/packages/kalimulhaq/pulse-cronwatch)
[![Total Downloads](https://img.shields.io/packagist/dt/kalimulhaq/pulse-cronwatch.svg?style=flat-square)](https://packagist.org/packages/kalimulhaq/pulse-cronwatch)
[![GitHub Stars](https://img.shields.io/github/stars/kalimulhaq/pulse-cronwatch?style=flat-square)](https://github.com/kalimulhaq/pulse-cronwatch/stargazers)
[![CI](https://github.com/kalimulhaq/pulse-cronwatch/actions/workflows/main.yml/badge.svg?branch=main)](https://github.com/kalimulhaq/pulse-cronwatch/actions/workflows/main.yml)
[![PHP](https://img.shields.io/packagist/php-v/kalimulhaq/pulse-cronwatch.svg?style=flat-square&logo=php&logoColor=white)](https://packagist.org/packages/kalimulhaq/pulse-cronwatch)
[![Laravel](https://img.shields.io/badge/Laravel-11%2B-FF2D20?style=flat-square&logo=laravel&logoColor=white)](https://laravel.com)
[![License](https://img.shields.io/github/license/kalimulhaq/pulse-cronwatch?style=flat-square)](LICENSE.md)

**Laravel Pulse Cronwatch** is a [Laravel Pulse](https://pulse.laravel.com) card that monitors your scheduled commands (cron jobs) — every command from your scheduler, when it last ran, how often it succeeds vs fails, and when it's next due.

The card lists every task your scheduler knows about — even ones that have never fired yet — and joins them with a live record of every run captured from Laravel's scheduler events.

**Capabilities at a glance:**

| Column | What it shows |
|--------|--------------|
| `Command` | The scheduler's display signature, normalised to the clean artisan name (closures and `->name(…)` labels are preserved as-is); hover shows the full shell-wrapped form |
| `Cron` | The cron expression from the live schedule, e.g. `0 1 * * *` |
| `Next due` | Human-readable countdown to the next run, hover for absolute timestamp |
| `Last run` | Time-ago of the most recent terminal event, or `Never` |
| `Avg` | Average successful run duration with adaptive unit (`ms` / `s` / `m` / `h`); raw `ms` in the cell tooltip |
| `Success` | Count of successful runs in the period |
| `Failed` | Count of failed runs in the period (highlighted red when > 0) |
| `Skipped` | Count of runs the scheduler skipped (overlap protection, `when()` callbacks, etc.) |

**Requires:** PHP 8.2+ · Laravel 11+ · Laravel Pulse 1.4+ · Livewire 3+

---

## Installation

```bash
composer require kalimulhaq/pulse-cronwatch
```

The service provider is auto-discovered by Laravel.

### Register the recorder

Add the recorder class to your `config/pulse.php` under `recorders`:

```php
'recorders' => [
    \Kalimulhaq\PulseCronwatch\Recorders\Schedule::class => [],

    // …your existing Pulse recorders…
],
```

There are no per-recorder options at this time — the empty `[]` is intentional.

### Add the card to your Pulse dashboard

If you haven't already, publish Pulse's dashboard view:

```bash
php artisan vendor:publish --tag=pulse-dashboard
```

Then add the card to `resources/views/vendor/pulse/dashboard.blade.php` wherever it fits in your grid:

```blade
<livewire:cronwatch.schedule cols="full" />
```

The `cols` attribute accepts any Pulse column value (`1`–`12` or `full`). `rows` is supported the same way other Pulse cards expose it. Pulse's period selector (1h / 6h / 24h / 7d) applies to the card automatically.

---

## Configuration

There is no published config file for v0.x — the package uses defaults from Pulse and Laravel directly. The card's only stateful option is the active sort, which is stored in the URL via Livewire:

| URL parameter | Values | Default |
|---|---|---|
| `cronwatch-order-by` | `next_due`, `failed`, `success`, `avg_duration`, `last_run` | `next_due` |

---

## Quick start

Once installed and registered, the card needs no further setup. To see it populate quickly:

```bash
# List what Laravel knows about (sanity check)
php artisan schedule:list

# Run the scheduler in the foreground for a minute or two
php artisan schedule:work
```

After at least one task fires, refresh `/pulse` — that task's row updates with its success/failed/skipped counts, last-run timestamp, and duration. All other scheduled tasks already appear in the table with cron + next due even if they haven't run yet.

---

## Pulse Card Display

```
┌────────────────────────────────────────────────────────────────────────────────────────────────┐
│  ⏱  Scheduled Commands                                              Sort by: next due  ▼  past hour │
├──────────────────────────────────────┬───────────┬───────────┬───────────┬───────┬─────┬──────┬──────┤
│ Command                              │ Cron      │ Next due  │ Last run  │ Avg   │ ✓   │  ✗   │  ⊘   │
├──────────────────────────────────────┼───────────┼───────────┼───────────┼───────┼─────┼──────┼──────┤
│ app:hourly-sync                      │ 0 * * * * │ in 23 min │ 37 min ago│ 412ms │ 124 │  0   │  2   │
│ app:sync-properties                  │ 0 2 * * * │ in 14 hr  │ 14h ago   │ 1.4s  │  7  │  1   │  0   │
│ app:migrate-developers               │ 30 1 * * *│ in 13 hr  │ Never     │  —    │  0  │  0   │  0   │
│ app:weekly-report                    │ 0 0 * * 1 │ in 6 days │ 2 wks ago │ 5.1s  │  4  │  0   │  0   │
└──────────────────────────────────────┴───────────┴───────────┴───────────┴───────┴─────┴──────┴──────┘
```

Sorting is configurable via the dropdown — defaults to **next due** (soonest first). Each row's hover state reveals the absolute timestamp behind the relative time strings.

---

## Data Collection

```
┌──────────────────────────────────────────────────────────────────────────────┐
│  System cron fires every minute:                                             │
│  * * * * * cd /your/app && php artisan schedule:run >> /dev/null 2>&1        │
└──────────────────────────────────────────────────────────────────────────────┘
                                  │
                                  ▼
┌──────────────────────────────────────────────────────────────────────────────┐
│  Laravel's scheduler inspects routes/console.php (or Kernel.php),            │
│  runs the due tasks, and dispatches three lifecycle events per task:         │
│   - Illuminate\Console\Events\ScheduledTaskFinished  (success + runtime)     │
│   - Illuminate\Console\Events\ScheduledTaskFailed                            │
│   - Illuminate\Console\Events\ScheduledTaskSkipped                           │
└──────────────────────────────────────────────────────────────────────────────┘
                                  │
                                  ▼
┌──────────────────────────────────────────────────────────────────────────────┐
│  This package's recorder catches each event and writes to Pulse storage:     │
│   - pulse_entries    ← one row per terminal event (count + duration)         │
│   - pulse_aggregates ← bucketed per-period counts/max/avg                    │
│   - pulse_values     ← latest `schedule_last_run` per command                │
└──────────────────────────────────────────────────────────────────────────────┘
                                  │
                                  ▼
┌──────────────────────────────────────────────────────────────────────────────┐
│  When the dashboard loads, the Livewire card OUTER-JOINS:                    │
│    • Laravel's live Schedule registry  → cron + next due + full task list   │
│    • Pulse storage rows                → counts + last run + duration        │
│  Tasks scheduled but not yet fired show with `Never` + 0 counts.             │
│  Tasks no longer scheduled (removed from console.php) still appear with      │
│  their historical run data; cron + next due render as `—`.                   │
└──────────────────────────────────────────────────────────────────────────────┘
```

**Important:** the recorder only records what actually runs. If your system cron isn't firing `php artisan schedule:run` every minute (or `php artisan schedule:work` in dev), the card will show "Never" for everything except commands you trigger manually. Verify with `php artisan schedule:list` to confirm Laravel sees your scheduled tasks.

---

## Caveats

- **Closure-scheduled tasks without `->name('…')` collide.** Two closures both default to the same `"Closure"` display signature, so they merge into one row in the card. Use `->name('stable-name')` on each scheduled closure to keep them separate.
- **Command rename = new row.** The card's join key is the scheduler's display signature. If you rename an artisan command or change its arguments, the new signature is treated as a different row; the old signature keeps its history.
- **Pulse storage trimming applies.** Pulse auto-trims old entries per `pulse.storage.trim.keep` (default 7 days). Cronwatch rows follow the same retention; historical data older than the trim window won't appear.
- **Cron / Next due require live schedule access.** The card reads `app(Illuminate\Console\Scheduling\Schedule::class)->events()` at render time. Web requests don't auto-bootstrap that registry, so the card resolves the Console Kernel for you — if you've heavily customised kernel bootstrapping, ensure `App\Console\Kernel::schedule()` (or `routes/console.php`) remains discoverable.

---

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for recent changes.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security

If you discover a security issue please email [kalim.dir@gmail.com](mailto:kalim.dir@gmail.com) rather than using the public issue tracker.

## Credits

- [Kalim ul Haq](https://github.com/kalimulhaq)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
