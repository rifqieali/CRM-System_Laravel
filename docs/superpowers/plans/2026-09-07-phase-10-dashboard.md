# Phase 10 — Dashboard Polish

**Date:** 2026-09-07
**Parent spec:** `docs/superpowers/specs/2026-09-06-crm-core-foundation-design.md` §5 Dashboard, §6 tests, §7 row 10
**Phase:** 10 of 11
**Goal:** Polish existing dashboard per spec §5: time-aware greeting, "Anda punya N deals closing minggu ini" callout, properly-scoped recent-activity feed, parent-entity link in feed, consistent meeting badge color.

---

## What was already in place

Phase 4 shipped a working `DashboardController` + `resources/views/dashboard.blade.php` with:
- Generic greeting "Selamat datang, {name}"
- Subtitle "Berikut ringkasan aktivitas CRM Anda."
- 4 KPI cards: Pipeline Value, Deals Won This Month, Activities Due Today, New Contacts This Week
- "Aktivitas Terbaru" card listing 10 latest activities (in-memory filter via `$user->can('view', $activity)`)
- `DashboardTest` with 4 tests (guest redirect, render, name in greeting, verified email required)

## Deliverables for Phase 10

### Controller changes (`App\Http\Controllers\DashboardController`)

- Time-aware greeting: `match` on hour → `Selamat pagi` (< 11) / `Selamat siang` (< 15) / `Selamat sore` (< 18) / `Selamat malam` (default). Replaces generic "Selamat datang".
- New `dealsClosingThisWeek` KPI: counts `Deal::scopedTo($user)` where `stage NOT IN (won, lost)` AND `expected_close_date` falls within current week. Drives the spec §5 callout "Anda punya {N} deals yang closing minggu ini".
- `whereBetween` now uses `->toDateString()` to avoid SQLite text-comparison quirks with full datetime strings vs the model's `date`-cast column.
- Recent-activities feed refactored from in-memory `->filter('view')` (fetches 10, then narrows by policy) to query-level `orWhereHasMorph` over Contact/Company/Deal with `ScopedToUser` predicate. Admin/Manager see all; Sales see own + same-`manager_id` team. Same pattern as `Activities\Index` and `Notes\Index`.
- Local `scopeMorphOwner` helper inside the controller (duplicated from `Activities\Index` + `Notes\Index` — future refactor opportunity: extract a `MorphOwnerScope` trait, deferred until ≥3 consumers exist; we now have 3).

### View changes (`resources/views/dashboard.blade.php`)

- Header uses `$greeting` (time-aware) instead of hard-coded "Selamat datang".
- Subtitle becomes the spec §5 callout: shows "Anda punya N deals yang closing minggu ini." when `dealsClosingThisWeek > 0`, otherwise neutral "Tidak ada deal yang closing minggu ini." N is rendered with a slightly heavier font weight.
- Recent-activities list now shows the parent-entity name (Contact full name, Company name, or Deal name) in addition to the author and the `diffForHumans` timestamp.
- Meeting badge color changed from `emerald` to `purple` to match the `Activities\Index` and `Notes\Index` page convention (consistency across the app).

### Tests (`tests/Feature/DashboardTest`)

Extended from 4 → 7 tests:
- 4 existing tests kept (with "Selamat datang" → "Selamat" so the assertion is robust across time-of-day).
- 3 new tests:
  - `shows deals closing this week callout when present` — uses `Carbon::setTestNow('2026-09-09 10:00:00')` to deterministically land inside a known Mon-Sun week, creates 1 deal with `expected_close_date = '2026-09-11'`, asserts response contains "1" and "deals yang closing minggu ini". `Carbon::setTestNow()` reset at end.
  - `shows neutral callout when no deals closing this week` — empty user, asserts "Tidak ada deal yang closing minggu ini."
  - `scopes recent activities to user` — Sales1 creates a contact activity with subject "MineRecent", Sales2 creates "OtherRecent"; Sales1 dashboard shows "MineRecent" but not "OtherRecent" (cross-team scope enforced at query level, not in-memory filter).

## Quality gates

- `composer pint --test` — clean
- `composer stan` (level 5, 1G memory) — 0 errors
- `vendor/bin/pest` — 177/177 green (stable across 5 consecutive runs)

## Out of scope (later phases)

- Phase 11: global search, README, GitHub Actions CI, polish
- Sub-project #6: real KPI computation, charts, forecasting

## Notes for reviewers

- Time-aware greeting is locale-naive: uses `now()->format('H')` which is UTC by app default. For Indonesia time-zone display, set `config('app.timezone')` to `Asia/Jakarta` in production. Out of scope for Core Foundation.
- `expected_close_date` is stored as a `date` (Carbon-cast to 'Y-m-d' string in SQLite). The `whereBetween` clause now uses `->toDateString()` to ensure both sides of the comparison are plain `Y-m-d` strings, sidestepping SQLite's text lexicographic comparison on the `T00:00:00.000000Z` form Carbon produces.
- The "you have N deals closing this week" callout plural uses bare "deals" (Indonesian has no separate singular/plural form). Numbers 0 → neutral copy; 1+ → "N deals".
- The recent-activities refactor reduces N+1 risk: instead of loading 10 then filtering, the query now produces the final 10 already scoped. Same row count, but no wasted work and the "Latest 10" guarantee now actually holds for Sales users (was silently shrinking before).
- `scopeMorphOwner` is now duplicated in `DashboardController`, `Activities\Index`, and `Notes\Index`. Future refactor: extract `MorphOwnerScope` trait with a static method that takes the morph-class array. Defer until we have 3+ consumers (we do), so worth filing a follow-up issue in sub-project #2 (Sales Pipeline) cleanup.