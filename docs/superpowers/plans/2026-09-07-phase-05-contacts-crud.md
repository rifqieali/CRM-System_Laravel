# Phase 5 — Contacts CRUD

**Date:** 2026-09-07
**Parent spec:** `docs/superpowers/specs/2026-09-06-crm-core-foundation-design.md` §3 (contacts), §4 (scoping), §5 (list/form/show/shared components), §6 (ContactCrudTest, ActivityPolymorphicTest, NotePolymorphicTest, TagAttachDetachTest)
**Phase:** 5 of 11
**Goal:** Full CRUD UI for Contacts via Livewire 3 — listing + filters, create/edit forms, show page with tabs (Overview / Activities / Notes / Related Deals), tag attach/detach, activity create, note create. Ownership-scoped through `ContactPolicy` + `ScopedToUser`.

---

## What's already in place from Phase 1–4

- Laravel 12.69 + Livewire 3 + Tailwind 4 + Spatie Permission + Pest 3
- 6 Eloquent models (Contact, Company, Deal, Activity, Note, Tag) with relations + `ScopedToUser` scope on Contact/Company/Deal
- 4 ownership policies (Contact, Company, Deal, Activity)
- `RolePermissionSeeder` (32 permissions, 3 roles) + `UserSeeder` (5 dev users)
- `App\Support\Ownership::check()` for Sales team-scope
- Auth pages + protected `app.blade.php` layout with sidebar/topnav
- 9 Tailwind Blade components (button, input, textarea, select, card, badge, modal, empty-state, toast)

## Deliverables for Phase 5

### FormRequest (`app/Http/Requests/ContactRequest.php`)

- `first_name` required string max 100
- `last_name` required string max 100
- `email` required email max 255 unique-ignore-self (Edit mode)
- `phone` nullable string max 50
- `mobile` nullable string max 50
- `job_title` nullable string max 150
- `company_id` nullable exists:companies,id
- `owner_id` required exists:users,id
- `source` nullable in:website,referral,cold-call,event,other
- `notes` nullable string
- `tag_ids` nullable array
- `tag_ids.*` exists:tags,id

### Livewire components (`app/Livewire/Contacts/`)

- **`Index.php`** — Table view with filters (search, owner, source, date range). Pagination 15/page. Reactive using `WithPagination`. Owner dropdown: Admin/Manager = all users, Sales = self + team. Delete action visible per policy. Empty state when no records. Header with "New Contact" button.

- **`Form.php`** — Single component for Create + Edit (route-bound `?id=`). Uses `#[Url]` for query params. Validation via injected `ContactRequest`. Authorize via `ContactPolicy::create/update`. "Save" → redirect to show. "Save & Add Another" → reset, toast. On edit, calls `$this->authorize('update', $contact)` in `mount()`.

- **`Show.php`** — Header with full name, action buttons (Edit, Delete, Log Activity, Add Note). Tabbed sections:
  - **Overview** — all fields + owner info + tags + timestamps
  - **Activities** — timeline (newest first), filter by type (all/call/email/meeting/task)
  - **Notes** — list + add form (uses `NoteEditor` shared)
  - **Related Deals** — table of linked deals
- Right sidebar: Owner badge, tags (clickable filter), timestamps, delete confirm modal.
- Delete via `$this->authorize('delete', $contact)` + soft delete.

### Shared Livewire components (`app/Livewire/Shared/`)

- **`ActivityTimeline.php`** — Renders activities chronologically with type filter. Used in Show pages (Contact/Company/Deal) + Dashboard.

- **`NoteEditor.php`** — Textarea + submit, displays recent notes list. Used in Show pages.

- **`TagSelector.php`** — Multi-select with create-new-inline. Loads all tags, allows inline tag creation (uses Tag model auto-slug from Phase 3). Sync via `tags()->sync()` on parent record.

- **`OwnerBadge.php`** — User avatar + name. Reusable in tables + show pages.

- **`LogActivityModal.php`** — Modal form to create a polymorphic activity on a record. Type, subject, description, due_at, completed_at.

### Routes (`routes/web.php`)

Add inside `auth` + `verified` middleware group:

```
GET  /contacts                  contacts.index   App\Livewire\Contacts\Index
GET  /contacts/create           contacts.create  App\Livewire\Contacts\Form
GET  /contacts/{contact}        contacts.show    App\Livewire\Contacts\Show
GET  /contacts/{contact}/edit   contacts.edit    App\Livewire\Contacts\Form
```

Plus update sidebar `Contacts` link from `#` to `route('contacts.index')`.

### Views

- `resources/views/livewire/contacts/index.blade.php` — table + filter bar + pagination
- `resources/views/livewire/contacts/form.blade.php` — create/edit form
- `resources/views/livewire/contacts/show.blade.php` — tabs + sidebar
- `resources/views/livewire/shared/activity-timeline.blade.php`
- `resources/views/livewire/shared/note-editor.blade.php`
- `resources/views/livewire/shared/tag-selector.blade.php`
- `resources/views/livewire/shared/owner-badge.blade.php`
- `resources/views/livewire/shared/log-activity-modal.blade.php`

## Tests

- **`tests/Feature/ContactCrudTest.php`** — index renders, create form posts valid → redirects to show, validation errors, edit form loads, update posts valid, delete requires policy, delete soft-deletes, ownership scoping (Sales cannot see/manage other team's records).
- **`tests/Feature/ActivityPolymorphicTest.php`** — create activity on Contact (factory-driven + Livewire via `LogActivityModal`), activity visible on Show page timeline.
- **`tests/Feature/NotePolymorphicTest.php`** — add note on Contact, displayed in Notes tab.
- **`tests/Feature/TagAttachDetachTest.php`** — create tag inline via TagSelector, attach to contact, detach, list visible.

Total expected: 80 (Phase 4) + ~20 new = ~100 tests.

## Quality gates

- `composer pint --test` — clean
- `composer stan` (level 5, 1G memory) — 0 errors
- `vendor/bin/pest` — all green
- `npm run build` — succeeds
- Manual smoke: register → admin login → create Company "Acme Corp" → create Contact "Budi" linked → log Activity (call) → add Note → attach tag "VIP" → logout → login as sales1@crm.test → verify only own visible.

## Out of scope (later phases)

- Phase 6: Companies CRUD (reuses patterns from Phase 5)
- Phase 7: Deals CRUD
- Phase 8: cross-entity Activities page
- Phase 9: Notes & Tags as standalone
- Phase 10: dashboard live data
- Phase 11: global search, CI, README polish

## Notes for reviewers

- Livewire 3 single-file components chosen over class-per-route for speed; spec explicitly allows `#[Modelable]` reuse.
- `LogActivityModal` lives at the deal/contact level (not global) — same pattern reused in Phase 7.
- Filters use `#[Url]` for bookmarkable URLs but skip query string syncing for Core MVP.
- Tests use Livewire `Volt`-style testing helpers (`Livewire::test(...)`) for form interactions.
- `Company::pluck('name','id')` populated in Create form via lazy load (200 max).