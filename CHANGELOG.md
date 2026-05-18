# Changelog

All notable changes to `kalimulhaq/pulse-cronwatch` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

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
