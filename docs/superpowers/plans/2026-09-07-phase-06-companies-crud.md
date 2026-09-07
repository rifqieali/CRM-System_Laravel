# Phase 6 — Companies CRUD

**Date:** 2026-09-07
**Parent spec:** `docs/superpowers/specs/2026-09-06-crm-core-foundation-design.md` §3 (companies), §4, §5, §6, §7
**Phase:** 6 of 11
**Goal:** Companies CRUD via Livewire 3, mirroring Phase 5 Contact patterns. Adds Related Contacts tab on Show page.

---

## What's already in place

- Phase 1–5: scaffold, schema, models+policies, auth+layout, Contacts CRUD + shared Livewire components
- Phase 5 shared: `ActivityTimeline`, `NoteEditor`, `TagSelector`, `OwnerBadge`, `LogActivityModal`
- `App\Models\Company` with ScopedToUser, HasOwner, HasTags, SoftDeletes
- `CompanyPolicy` (Admin/Manager always; Sales owner or same-manager team)

## Deliverables for Phase 6

### FormRequest (`app/Http/Requests/CompanyRequest.php`)

- name required
- industry in:tech,finance,healthcare,retail,manufacturing,other nullable
- size in:1-10,11-50,51-200,201-500,500+ nullable
- website nullable string max:255 (URL format not strictly enforced — Livewire empty-string quirk; user corrects in UI)
- phone nullable max:50
- address nullable max:255
- city nullable max:100
- country nullable max:100
- owner_id required exists
- notes nullable
- tag_ids array of existing tag IDs

### Livewire (`app/Livewire/Companies/`)

- **Index** — table + filter bar (search name/website, owner, industry, size, date range) + pagination 15/page + delete (policy-gated)
- **Form** — Create + Edit; inline tag create + sync; Save & Add Another
- **Show** — tabs: Overview / Contacts / Deals / Activities / Notes (per spec §5: Related Contacts added as own tab for Company)

### Routes

```
GET  /companies                  companies.index
GET  /companies/create           companies.create
GET  /companies/{company}        companies.show
GET  /companies/{company}/edit   companies.edit
```

Sidebar Companies link wired to `companies.index`.

### Reused from Phase 5

- All 5 shared Livewire components (ActivityTimeline, NoteEditor, TagSelector, OwnerBadge, LogActivityModal)
- Tailwind Blade components (button, input, textarea, select, card, badge, modal, empty-state)
- Auth flow + layout (Phase 4)

## Tests (134 passing, +19 from Phase 5)

| File | Tests | Coverage |
|------|------:|----------|
| `tests/Feature/CompanyCrudTest` | 19 | index renders, scope filter, search, industry filter, admin sees all, sales cannot see outsider, create form, name required, industry enum, website field (nullable), edit-loads, update, save-and-add-another, edit forbid, show, show forbid, related contacts tab, delete, delete forbid |

## Quality gates

- `composer pint --test` — clean
- `composer stan` (level 5, 1G memory) — 0 errors
- `vendor/bin/pest` — 134/134 green

## Out of scope (later phases)

- Phase 7: Deals CRUD
- Phase 8: cross-entity Activities page
- Phase 9: standalone Notes & Tags
- Phase 10: dashboard live data
- Phase 11: global search, CI, README

## Notes for reviewers

- Website URL format intentionally not strictly validated (Laravel `url` rule rejects empty strings; Livewire empty-string quirk). String max:255 enforced.
- All other validation enums (industry, size) match spec §3 table enums.
- Show page tabs follow spec §5 ordering for Company: Overview / Contacts / Deals / Activities / Notes.
- Save & Add Another reset flow matches Contact pattern from Phase 5.