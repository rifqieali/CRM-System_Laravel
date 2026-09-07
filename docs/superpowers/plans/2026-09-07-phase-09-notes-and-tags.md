# Phase 9 — Notes Cross-Entity Feed + Tags Index

**Date:** 2026-09-07
**Parent spec:** `docs/superpowers/specs/2026-09-06-crm-core-foundation-design.md` §3 notes/tags, §5 list page, §6 tests, §7 row 9
**Phase:** 9 of 11
**Goal:** Notes cross-entity feed (`/notes`) mirror of Activities, plus standalone `Tags\Index` CRUD page (`/tags`). `NoteEditor` and `TagSelector` already shipped in Phase 5–7 — Phase 9 adds the missing list pages + `NotePolicy` + `TagPolicy` for standalone endpoints.

---

## What was already in place

- Phase 2 schema: `notes` polymorphic (Contact/Company/Deal), `tags` + `taggables` pivot, hex color.
- Phase 3: `App\Models\Note` (TYPES-free, just body + user + morph) and `App\Models\Tag` (auto-slug, color).
- Phase 5–7: `App\Livewire\Shared\NoteEditor` (textarea + submit, in-place on Show pages) and `App\Livewire\Shared\TagSelector` (multi-select with inline-create).
- Phase 5–7: `NotePolymorphicTest` (5 tests) + `TagAttachDetachTest` (6 tests) — both green, untouched.

## Deliverables for Phase 9

### Models / Policies (no model changes)

- `App\Policies\NotePolicy` — viewAny/view/create/update/delete; `update` allows author user_id OR parent ownership; `delete` mirrors update. Mirrors `ActivityPolicy` pattern.
- `App\Policies\TagPolicy` — flat permission check; tags have no `owner_id`, so all auth'd users can view, but only roles with create-tag/update-tag/delete-tag perms can mutate (per RolePermissionSeeder). All roles can view; everyone can create/edit/delete since `view/manage Tags` is universal per spec §4.

### FormRequest

- `App\Http\Requests\TagRequest` — name required max 100, color nullable hex regex `^#[0-9A-Fa-f]{6}$`. Indonesian messages.

### Livewire

**`App\Livewire\Notes\Index`** — cross-entity feed:
- `WithPagination` 15/page
- `#[Url]` filters: `search` (body LIKE), `entityType` (Contact/Company/Deal FQCN), `ownerId` (user_id), `dateFrom`/`dateTo` (created_at range)
- Cross-entity scope via `orWhereHasMorph` on Contact/Company/Deal with `ScopedToUser` predicate (Admin/Manager see all; Sales see own + same-`manager_id` team)
- `ownersList` computed — same pattern as Activities
- `delete(int $noteId)` — policy-gated, hard delete (no soft deletes on `notes` per spec §3)
- `resetFilters()` — clears all filters + pagination

**`App\Livewire\Tags\Index`** — full CRUD page:
- `WithPagination` 15/page
- `#[Url]` filters: `search` (name/slug LIKE), `sort` (name|slug|created_at), `sortDirection` (asc|desc)
- Inline create + edit form (toggle `showForm`, `editingId` state)
- `openCreate()` / `openEdit(int)` / `cancelForm()` / `save()` — `save()` runs `TagRequest` rules inline (same pattern as `ActivityRequest` in Phase 8 — kept for symmetry; `LogActivityModal`/`TagSelector` continue to use inline `#[Validate]`)
- Slug uniqueness handled by appending `-2`, `-3`, etc.
- `delete(int)` — policy-gated, hard delete (cascades to `taggables` via FK)
- `#[Computed] usageCounts` — count of `taggables` rows per tag across all 3 morph types (one query per morph via `\DB::table('taggables')`)
- `sortBy(string)` — toggle direction if same field, reset to asc otherwise

### Routes (2 new, `auth` + `verified`)

```
GET  /notes  → notes.index
GET  /tags   → tags.index
```

### Views

- `resources/views/livewire/notes/index.blade.php` — filter bar (Cari/Entitas/Pelaku/Dari/Sampai/Reset) + table (Isi/Entitas link to Show/Pelaku/Tanggal/Aksi delete) + empty state + pagination
- `resources/views/livewire/tags/index.blade.php` — header with `+ Tag baru` button + inline form + filter bar + table (sortable Nama/Slug/Warna/Penggunaan/Aksi) + empty state + pagination

### Sidebar

`Notes` + `Tags` links wired to their `*.index` routes with `wire:navigate` + active state matching `notes.*` / `tags.*`.

## Tests (198 passing, +24 from Phase 8)

| File | Tests | Coverage |
|------|------:|----------|
| `tests/Feature/NotesTagsIndexTest` | 24 | notes guest redirect, notes render, notes admin sees all, notes sales scope (own + team), notes search (body), notes entity-type filter, notes owner filter, notes delete authorized, notes delete forbid cross-team, notes resetFilters, notes morph whitelist (NoteEditor + direct), notes NoteEditor cross-team block, tags guest redirect, tags render, tags create, tags edit, tags delete, tags reject blank name, tags reject invalid color hex, tags slug collision suffix, tags sort by name asc/desc, tags search, tags usage count from contacts/companies/deals, sales create-and-attach via TagSelector |
| Phase 1–8 tests | 174 | unchanged |

## Quality gates

- `composer pint --test` — clean
- `composer stan` (level 5, 1G memory) — 0 errors
- `vendor/bin/pest` — 198/198 green

## Out of scope (later phases)

- Phase 10: dashboard recent-activity feed (will pull from `Activity`, not `Note` — per spec §5 "Recent Activity")
- Phase 10: dashboard notes preview (not in spec §5 cards)
- Phase 11: global search (will cover all entities including notes/tags)
- Sub-project #5: workflow automation on notes (mentions, assignments)

## Notes for reviewers

- `Note` has no `SoftDeletes` per spec §3 (line 244 explicit) — uses hard `delete()`. Phase 9 same as Phase 8.
- `Tag` has no `owner_id` — viewable by all auth'd users, mutating gated by `create-tag` / `update-tag` / `delete-tag` perms which all 3 roles have per `RolePermissionSeeder`. So in practice all 3 roles can edit/delete; the policy surface is for future role changes.
- `TagSelector` (Phase 5) and `TagRequest` (Phase 9) duplicate the same validation rules intentionally — `TagSelector` keeps inline `#[Validate]` because it's a small inline-create UX on entity forms; `TagRequest` is the proper FormRequest for the standalone `Tags\Index` page where multiple rules + nicer error rendering are needed. Same pattern as `ActivityRequest` in Phase 8.
- `usageCounts` computed queries 3 times per render (one per morph type). Acceptable for tags listing where typical org has <100 tags. If we ever exceed, switch to a single grouped query: `SELECT tag_id, COUNT(*) FROM taggables GROUP BY tag_id`.
- Cross-entity scope replicates `orWhereHasMorph` over 3 morph types, identical to Phase 8's `Activities\Index`. Future refactor opportunity: extract a `MorphOwnerScope` trait that takes an array of morph classes, but only worth doing once we have ≥2 such consumers.
- `NotePolicy` mirrors `ActivityPolicy` 1:1 (except `noteable` vs `activityable` morph name). Future refactor opportunity: extract a base `MorphOwnerPolicy` class.