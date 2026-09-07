# Phase 8 — Activities Cross-Entity Feed

**Date:** 2026-09-07
**Parent spec:** `docs/superpowers/specs/2026-09-06-crm-core-foundation-design.md` §3 activities, §5 Activities list, §6 tests, §7 row 8
**Phase:** 8 of 11
**Goal:** Cross-entity activities feed (`/activities`) with filters + complete/delete actions; reuse existing `LogActivityModal` for in-place creation from Contact/Company/Deal Show pages.

---

## What is already in place

- Phase 2 schema: `activities` table polymorphic (Contact / Company / Deal), type enum (call/email/meeting/task), no soft deletes.
- Phase 3: `App\Models\Activity` with `TYPES` const, `user()`, `activityable()`, `guardMorphType()` boot hook whitelists morph class to `[Contact, Company, Deal]`.
- Phase 3: `App\Policies\ActivityPolicy` — viewAny/permission check + `Ownership::check()` against parent record for view/update/delete.
- Phase 5–7: `App\Livewire\Shared\LogActivityModal` — in-place activity creator with inline `#[Validate]` attrs; used from Contact/Company/Deal Show.
- Phase 5–7: `App\Livewire\Shared\ActivityTimeline` — chronological timeline for a single parent.

## Deliverables for Phase 8

### Livewire (`app/Livewire/Activities/Index.php`)

Single `Index` component (no separate `Create`/`Edit` because activities are logged from a parent record, never standalone-created):

- `WithPagination` (15/page)
- `#[Url]` filters: `search` (subject/description), `type` (call/email/meeting/task), `entityType` (Contact/Company/Deal class), `ownerId` (user_id), `dateFrom`/`dateTo` (created_at range), `dueFilter` (overdue/today/week/completed)
- Scoped query via `orWhereHasMorph` across all 3 morph types; Admin/Manager see all, Sales see own + same-`manager_id` team
- `ownersList` computed — Admin/Manager: all; Sales: self + same-`manager_id` team
- `delete(int $id)` — policy-gated soft delete not used; `delete()` on `Activity` (no SoftDeletes on `activities` table per spec §3)
- `markComplete(int $id)` — sets `completed_at = now()` (policy-gated)
- `resetFilters()` — clears all filters + resets pagination
- Live toast feedback on success via `session()->flash('status', ...)`

### FormRequest (`app/Http/Requests/ActivityRequest.php`)

Mirror of inline rules in `LogActivityModal` for symmetry with Phase 5–7 pattern. Not directly used yet (Livewire calls `validate()` inline) but ready for non-Livewire entry points.

### Routes

```
GET  /activities  → activities.index  (auth + verified)
```

### View (`resources/views/livewire/activities/index.blade.php`)

- Filter bar: search + type + entity + owner + due + date range + reset
- Table: Type badge (color per type) / Subject + description / Related entity (linked to Show) / Due date / Status (Selesai/Terlambat/Aktif) / Pelaku / Aksi (Selesai + Hapus)
- Empty state
- Pagination

### Sidebar

`Activities` link wired to `route('activities.index')` with `wire:navigate` + active state matching `activities.*`.

## Tests (174 passing, +18 from Phase 7)

| File | Tests | Coverage |
|------|------:|----------|
| `tests/Feature/ActivityFeedTest` | 18 | guest redirect, index render, admin sees all, sales scope (own + team), type filter, entity-type filter, overdue filter, due-within-7-days filter, completed filter, owner filter, search (subject/description), delete authorized, delete forbid (cross-team), mark complete, log via shared modal, reject invalid type, reject disallowed morph class, resetFilters |

## Quality gates

- `composer pint --test` — clean
- `composer stan` (level 5, 1G memory) — 0 errors
- `vendor/bin/pest` — 174/174 green

## Out of scope (later phases)

- Phase 9: Notes & Tags index
- Phase 10: dashboard recent-activity feed
- Phase 11: global search

## Notes for reviewers

- Activity has no `SoftDeletes` per spec §3 (line 233 explicit) — uses hard `delete()`. Soft-delete behaviour on Activities is therefore not in MVP; can revisit in sub-project #5 (Automation) when retention matters.
- Cross-entity scope is applied via `orWhereHasMorph` on 3 parent classes; each branch re-implements the `owner_id = user.id OR owner_id IN (team)` predicate because morph parents don't share a trait-able relation. Equivalent to `ScopedToUser` on the parent table.
- `markComplete` is a convenience for Phase 8; deeper workflow (re-open, recurring activities) is sub-project #5.
- `LogActivityModal` was already in place from Phase 5; Phase 8 wraps it in tests to ensure it still works against the new scope/policy surface.
- `dueFilter=today` and `dueFilter=week` only show non-completed, due-`not null` activities to match the "what's left to do" mental model.