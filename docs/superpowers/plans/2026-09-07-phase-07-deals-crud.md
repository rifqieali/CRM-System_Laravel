# Phase 7 — Deals CRUD

**Date:** 2026-09-07
**Parent spec:** `docs/superpowers/specs/2026-09-06-crm-core-foundation-design.md` §3 (deals), §4, §5, §6, §7
**Phase:** 7 of 11
**Goal:** Deals CRUD via Livewire 3, with Contact+Company pickers, Stage badges, model auto-set `closed_at` when stage hits won/lost.

---

## What's already in place

- Phase 1–6: scaffold, schema, models+policies, auth+layout, Contacts CRUD, Companies CRUD + shared Livewire components
- `App\Models\Deal` with `STAGES`, `CLOSED_STAGES`, `saving` boot hook auto-setting `closed_at`, ScopedToUser, HasOwner, HasTags, SoftDeletes, contact/company/owner/activities/notes relations
- `DealPolicy` (Admin/Manager always; Sales owner or same-manager team)
- `DealFactory` with id_ID Faker
- `App\Models\Concerns\HasTags` (no `withTimestamps()`)

## Deliverables for Phase 7

### FormRequest (`app/Http/Requests/DealRequest.php`)

- name required
- value required numeric min:0 max:999999999999.99
- currency required size:3
- stage required in `Deal::STAGES`
- probability nullable integer 0–100
- expected_close_date nullable date
- contact_id required exists
- company_id required exists
- owner_id required exists
- tag_ids array of existing tag IDs

### Livewire (`app/Livewire/Deals/`)

- **Index** — table + filter bar (search name, owner, stage, company, close date range) + pagination 15/page + delete (policy-gated) + empty state + reset-filters
- **Form** — Create + Edit; inline tag create + sync; Save & Add Another; auto-fill company_id when contact_id chosen and company_id empty
- **Show** — header + stage badge (color-coded) + tabs (Overview / Activities / Notes) + right sidebar (Owner, Tags, Timestamps) + delete confirmation

### Routes

```
GET  /deals                  deals.index
GET  /deals/create           deals.create
GET  /deals/{deal}           deals.show
GET  /deals/{deal}/edit      deals.edit
```

Sidebar Deals link wired to `deals.index`.

### Badge component

Added `purple` color to `resources/views/components/badge.blade.php` for negotiation stage.

## Tests (156 passing, +22 from Phase 6)

| File | Tests | Coverage |
|------|------:|----------|
| `tests/Feature/DealCrudTest` | 22 | index renders, scope filter, search, stage filter, admin sees all, sales team scoping, create form, name/value/currency/stage required, stage enum, probability range, contact+company required, edit-loads, update, **stage→won auto-sets closed_at**, **stage→negotiation clears closed_at**, save-and-add-another, edit-forbid, show, show-forbid, delete, delete-forbid |

## Quality gates

- `composer pint --test` — clean
- `composer stan` (level 5, 1G memory) — 0 errors
- `vendor/bin/pest` — 156/156 green

## Out of scope (later phases)

- Phase 8: cross-entity Activities page
- Phase 9: standalone Notes & Tags index
- Phase 10: dashboard live data
- Phase 11: global search, CI, README

## Notes for reviewers

- Show page tab order follows spec §5: Overview / Activities / Notes. No Related Deals on Deal itself (per spec §5 — Related Deals on Contact/Company only).
- `closed_at` is auto-managed by `Deal::saving` boot hook — covered by 2 tests.
- Stage color map: prospecting=zinc, qualification=blue, proposal=amber, negotiation=purple, won=emerald, lost=red.
- Contact picker auto-fills company_id from contact only when company_id is empty (UX convenience, does not overwrite user choice).
- `expected_close_date` stored as date, shown as `d M Y` in tables / detail.
- Currency defaults to `IDR` per spec §3.
- Save & Add Another matches Contact/Company pattern from Phase 5/6.