# Changelog

All notable changes to `kalimulhaq/pulse-cronwatch` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [0.3.0] — 2026-05-19

### Fixed
- **Command column no longer shows the shell-wrapped form** (`'/usr/bin/php8.3' 'artisan' migration:foo > '/dev/null' 2>&1`) for string-scheduled artisan commands. The clean signature (`migration:foo`) is rendered, with the original shell form preserved as the cell tooltip. Closures and `->name('…')`-labelled tasks pass through unchanged.
- **Cron and Next due columns now populate inside web requests.** The Schedule registry singleton is bound by the Console Kernel's `defineConsoleSchedule()` callback, which only runs when the Console Kernel is constructed — web requests construct the HTTP Kernel only. The card now resolves `Illuminate\Contracts\Console\Kernel` before reading `LaravelSchedule::events()`, so the registry is populated regardless of request type.

### Added
- **Adaptive Avg unit** — durations render with the unit that fits the magnitude: `ms` for sub-second, `s` for sub-minute, `m` for sub-hour, `h` otherwise (e.g. `412ms`, `5.1s`, `2.5m`, `1.1h`). The raw millisecond count remains available as the cell tooltip.
- New `Kalimulhaq\PulseCronwatch\Support\Signature` and `Support\Duration` helpers (covered by unit tests).

### Notes
- Historical rows recorded before v0.3 are keyed by the shell-wrapped form; their cron/next-due cells will remain empty until those rows age out via Pulse's 7-day storage trim. No migration step is needed.
- The shared `Signature::normalize()` is used by both the recorder (write side) and the card's registry merge (read side), so join keys stay consistent.

## [0.2.0] — 2026-05-18

### Added
- **Card now joins Laravel's live Schedule registry at render time** with the recorded Pulse data. Every scheduled command appears the moment the dashboard loads — no longer waits for the recorder to catch its first run.
- **Cron expression column** — displayed via `Event::getExpression()`.
- **Next due column** — displayed via `Event::nextRunDate()`, formatted with `Carbon::diffForHumans()`; absolute ISO timestamp on hover.
- **Sort by "next due"** option in the card header dropdown. This is now the default sort (soonest-due first); commands not present in the live registry sort last.

### Changed
- Default `orderBy` is now `next_due` (was `failed`).
- The "Last run" column shows `Never` (instead of `—`) for commands that have not yet fired but are present in the schedule registry.

### Notes
- Two closure-scheduled tasks without `->name('…')` will share the signature `"Closure"` and collide into one row. Use `->name('stable-name')` on closures to keep them separate.

## [0.1.0] — 2026-05-18

### Added
- Initial release.
- Pulse recorder for Laravel scheduled task events (`ScheduledTaskFinished`, `ScheduledTaskFailed`, `ScheduledTaskSkipped`).
- Livewire card showing per-command success/failure/skipped counts, last run timestamp, max and average duration over the selected Pulse period.
