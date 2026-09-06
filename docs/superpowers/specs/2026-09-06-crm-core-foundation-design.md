# CRM System — Core Foundation Design

**Date:** 2026-09-06
**Sub-project:** #1 of 6 (Core Foundation)
**Status:** Approved for implementation planning
**Stack:** Laravel 11 + Livewire 3 + Tailwind + MySQL 8 + Spatie Permission + Pest 3

---

## 1. Context & Scope

This spec covers **Sub-Project #1: Core Foundation** of a larger CRM system. The full system was decomposed into 6 independent sub-projects, each with its own spec → plan → implementation cycle. Core Foundation establishes the data model, authentication, authorization, and UI patterns that all later sub-projects will extend.

### Sub-project Roadmap

| # | Sub-Project | Spec | Plan | Implementation |
|---|-------------|------|------|----------------|
| 1 | **Core Foundation** ← *this spec* | this doc | next | later |
| 2 | Sales Pipeline (Kanban + Drag-Drop) | future | future | future |
| 3 | Custom Fields & Forms | future | future | future |
| 4 | Email Integration (IMAP/SMTP) | future | future | future |
| 5 | Lead Scoring & Automation | future | future | future |
| 6 | Advanced Analytics & Forecasting | future | future | future |

### In Scope (Core Foundation)

- Authentication: register, login, logout, password reset, email verification
- RBAC via Spatie Laravel Permission
- Entities: Users, Contacts, Companies, Deals, Activities (call/email/meeting/task), Notes, Tags
- Full CRUD UI for all entities via Livewire 3 + Tailwind
- Dashboard skeleton with placeholder KPI cards
- Activity timeline (polymorphic) on Contact / Company / Deal show pages
- Global search and per-listing filters
- Tag attach / detach
- Polymorphic Notes
- Pest 3 test suite with ≥70% coverage target
- Pint + Larastan quality gates
- GitHub Actions CI

### Out of Scope (Deferred to Other Sub-Projects)

- Kanban board and drag-and-drop UI (sub-project #2)
- Custom field schema and dynamic form builder (sub-project #3)
- Email sync via IMAP/Gmail/Outlook (sub-project #4)
- Lead scoring algorithm and automation workflows (sub-project #5)
- Charts, forecasting, real KPI computation (sub-project #6)
- Multi-tenancy / organization scoping
- Public REST/JSON API (none required by current scope)
- File uploads / attachments (out of scope until email integration sub-project)

---

## 2. Architecture & Tech Stack

### Stack

| Layer | Choice | Rationale |
|-------|--------|-----------|
| Framework | Laravel 11 | Latest stable, native Livewire 3 integration |
| Language | PHP 8.3 | Modern features, type safety |
| Database | MySQL 8 | JSON column support, ubiquitous, easy local setup |
| Frontend | Livewire 3 + Tailwind CSS 3 + Alpine.js | Reactive UIs without SPA complexity |
| Auth/RBAC | Spatie Laravel Permission | Industry standard for Laravel RBAC |
| Testing | Pest 3 | Modern syntax over PHPUnit |
| Code style | Laravel Pint | PSR-12 + Laravel preset, zero-config |
| Static analysis | Larastan (level 5) | Catch type errors early |
| Queue | Database driver | No infra dependency; upgradeable to Redis later |
| Asset build | Vite | Laravel 11 default |

### Directory Structure (Standard Laravel)

```
CRM_SystemLaravel/
├── app/
│   ├── Console/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── DashboardController.php
│   │   │   └── ProfileController.php
│   │   ├── Requests/         # FormRequest per entity
│   │   └── Middleware/
│   ├── Livewire/
│   │   ├── Contacts/
│   │   │   ├── Index.php
│   │   │   ├── Create.php
│   │   │   ├── Edit.php
│   │   │   └── Show.php
│   │   ├── Companies/        # same 4-file pattern
│   │   ├── Deals/
│   │   ├── Activities/
│   │   └── Tags/
│   ├── Models/
│   │   ├── User.php
│   │   ├── Contact.php
│   │   ├── Company.php
│   │   ├── Deal.php
│   │   ├── Activity.php
│   │   ├── Note.php
│   │   └── Tag.php
│   ├── Policies/
│   │   ├── ContactPolicy.php
│   │   ├── CompanyPolicy.php
│   │   ├── DealPolicy.php
│   │   └── ActivityPolicy.php
│   ├── Providers/
│   └── View/Components/
├── database/
│   ├── factories/
│   ├── migrations/
│   └── seeders/
│       ├── RolePermissionSeeder.php
│       └── UserSeeder.php
├── resources/
│   ├── views/
│   │   ├── components/
│   │   ├── livewire/
│   │   └── layouts/
│   │       └── app.blade.php
│   └── css/
├── routes/
│   └── web.php
├── tests/
│   ├── Feature/
│   ├── Unit/
│   └── Pest.php
├── .github/workflows/ci.yml
├── composer.json
├── package.json
└── README.md
```

### Data Flow

```
Browser → Livewire component → FormRequest validation → Policy check →
Eloquent (Model + Relations) → MySQL → Livewire re-render → Browser
```

No JSON API in Core Foundation. If needed in future sub-projects, can be added alongside without disrupting Livewire UI.

### Architectural Boundaries

- **Livewire components are thin.** Validation lives in `FormRequest` classes; business logic lives in Eloquent models or dedicated Action classes (`app/Actions/`). Components orchestrate UI state only.
- **Policies own authorization.** No `if (auth()->user()->can(...))` sprinkled in components; rely on `$this->authorize(...)` in Livewire or `Gate::allows(...)` in models.
- **Polymorphic relations are typed.** `Activity` and `Note` use `MorphTo`/`MorphMany` with explicit `activityable_type` whitelist to prevent arbitrary class binding.

---

## 3. Data Model

### Tables

All tables use InnoDB, `utf8mb4_unicode_ci`, include `id`, `timestamps`, `soft_deletes` unless noted.

#### `users` (extends Laravel default)

| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| name | varchar(255) | |
| email | varchar(255) UNIQUE | |
| email_verified_at | timestamp nullable | |
| password | varchar(255) | |
| remember_token | varchar(100) nullable | |
| avatar_path | varchar(255) nullable | |
| manager_id | bigint nullable FK→users | for team scoping |
| created_at, updated_at, deleted_at | timestamp | |

#### `companies`

| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| name | varchar(255) | indexed |
| industry | enum nullable | tech, finance, healthcare, retail, manufacturing, other |
| size | enum nullable | 1-10, 11-50, 51-200, 201-500, 500+ |
| website | varchar(255) nullable | |
| phone | varchar(50) nullable | |
| address | varchar(255) nullable | |
| city | varchar(100) nullable | |
| country | varchar(100) nullable | |
| owner_id | bigint FK→users | indexed |
| notes | text nullable | |
| created_at, updated_at, deleted_at | timestamp | |

#### `contacts`

| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| first_name | varchar(100) | |
| last_name | varchar(100) | |
| email | varchar(255) | indexed |
| phone | varchar(50) nullable | |
| mobile | varchar(50) nullable | |
| job_title | varchar(150) nullable | |
| company_id | bigint nullable FK→companies | indexed |
| owner_id | bigint FK→users | indexed |
| source | enum nullable | website, referral, cold-call, event, other |
| notes | text nullable | |
| created_at, updated_at, deleted_at | timestamp | |

#### `deals`

| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| name | varchar(255) | |
| value | decimal(12,2) | |
| currency | varchar(3) default 'IDR' | ISO 4217 |
| stage | enum | prospecting, qualification, proposal, negotiation, won, lost |
| probability | tinyint nullable | 0–100; nullable in Core, auto-suggested in sub-project #5 |
| expected_close_date | date nullable | indexed |
| closed_at | timestamp nullable | set when stage becomes won or lost |
| contact_id | bigint FK→contacts | indexed |
| company_id | bigint FK→companies | indexed |
| owner_id | bigint FK→users | indexed |
| created_at, updated_at, deleted_at | timestamp | |

#### `activities` (polymorphic)

| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| type | enum | call, email, meeting, task |
| subject | varchar(255) | |
| description | text nullable | |
| due_at | timestamp nullable | indexed |
| completed_at | timestamp nullable | |
| user_id | bigint FK→users | the user who owns/logged the activity |
| activityable_type | varchar(255) | must be Contact, Company, or Deal (validated) |
| activityable_id | bigint | |
| created_at, updated_at | timestamp | no soft deletes |

#### `notes` (polymorphic)

| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| body | text | |
| user_id | bigint FK→users | |
| noteable_type | varchar(255) | Contact, Company, or Deal |
| noteable_id | bigint | |
| created_at, updated_at | timestamp | no soft deletes |

#### `tags`

| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| name | varchar(100) | |
| slug | varchar(100) UNIQUE | auto-generated from name |
| color | varchar(7) nullable | hex (#RRGGBB) |
| created_at, updated_at | timestamp | |

#### `taggables` (pivot)

| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| tag_id | bigint FK→tags | cascade on delete |
| taggable_type | varchar(255) | Contact, Company, or Deal |
| taggable_id | bigint | |
| created_at | timestamp | |
| Unique index | (tag_id, taggable_type, taggable_id) | |

### Relations Summary

- `User hasMany Contact/Company/Deal/Activity/Note` (as owner/author)
- `User belongsToMany Role` via `role_user` (Spatie)
- `User belongsTo User` (manager) — self-referential
- `Company hasMany Contact, hasMany Deal, morphToMany Tag`
- `Contact belongsTo Company, belongsTo User (owner), hasMany Deal, hasMany Activity (polymorphic), hasMany Note (polymorphic), morphToMany Tag`
- `Deal belongsTo Contact, belongsTo Company, belongsTo User (owner), hasMany Activity (polymorphic), hasMany Note (polymorphic), morphToMany Tag`
- `Activity morphTo activityable (Contact|Company|Deal), belongsTo User`
- `Note morphTo noteable (Contact|Company|Deal), belongsTo User`
- `Tag morphToMany Taggable`

### Indexes

All FK columns indexed. Additional indexes:
- `contacts.email`
- `companies.name`
- `deals.stage`
- `deals.expected_close_date`
- `activities.due_at`
- `taggables` unique composite `(tag_id, taggable_type, taggable_id)`

---

## 4. RBAC & Authorization

### Roles

Defined in `RolePermissionSeeder`:

| Role | Description |
|------|-------------|
| **Admin** | Full access; can manage users & roles |
| **Manager** | Full access to records; can view team dashboards (sub-project #6); cannot manage roles |
| **Sales** | CRUD only on records they own (`owner_id = user.id`); can view records owned by anyone with the same `manager_id` |

### Permissions

Registered in `RolePermissionSeeder`:

```
view-any-contact, view-contact, create-contact, update-contact, delete-contact
view-any-company, view-company, create-company, update-company, delete-company
view-any-deal, view-deal, create-deal, update-deal, delete-deal
view-any-activity, view-activity, create-activity, update-activity, delete-activity
view-any-note, view-note, create-note, update-note, delete-note
view-any-tag, view-tag, create-tag, update-tag, delete-tag
manage-users, manage-roles
```

### Role → Permission Mapping

| Permission | Admin | Manager | Sales |
|------------|-------|---------|-------|
| view-{any,record} on all entities | ✓ | ✓ | ✓ (scoped) |
| create/update/delete on Contact/Company/Deal/Activity/Note | ✓ | ✓ | ✓ (own only) |
| view/manage Tags | ✓ | ✓ | ✓ |
| manage-users | ✓ | — | — |
| manage-roles | ✓ | — | — |

### Ownership Scoping (Policy Logic)

For each `view`, `update`, `delete`:

```
Admin   → always true
Manager → always true
Sales   → $record->owner_id === $user->id
       OR $record->owner->manager_id === $user->manager_id (team scope)
```

For `viewAny`:

```
Admin/Manager → query without scope filter
Sales         → query WHERE owner_id = $user->id
                 OR owner_id IN (SELECT id FROM users WHERE manager_id = $user->manager_id)
```

Implemented via Eloquent local scope `ScopedToUser` applied in repository / query builder classes to avoid duplication.

### Seeded Dev Users

| Email | Role | Manager |
|-------|------|---------|
| admin@crm.test | Admin | null |
| manager@crm.test | Manager | null |
| sales1@crm.test | Sales | manager@crm.test |
| sales2@crm.test | Sales | manager@crm.test |
| sales3@crm.test | Sales | (no manager — sees only own) |

All seed users use password `password`.

---

## 5. UI/UX

### Master Layout (`resources/views/layouts/app.blade.php`)

- **Top nav:** logo (left), global search input (center, placeholder), user dropdown (right: profile, logout)
- **Side nav (collapsible on mobile):** Dashboard, Contacts, Companies, Deals, Activities, Tags, Users (Admin only)
- **Main content area** with page header slot and content slot
- **Toast notifications** via Livewire `dispatchBrowserEvent('toast')` → Alpine listener
- **Tailwind config:** neutral palette (zinc), accent blue, system-font stack
- Dark mode: deferred to sub-project #2

### Dashboard (`/`)

- Greeting: "Selamat pagi, {name}" + "Anda punya {N} deals yang closing minggu ini"
- 4 KPI cards (placeholder values from DB count queries; real computation in sub-project #6):
  - Total Pipeline Value (sum of `deals.value` where stage NOT IN won/lost)
  - Deals Won (count where stage=won this month)
  - Activities Due Today
  - New Contacts This Week
- "Recent Activity" feed: latest 10 activities across user's scoped records

### List Pages (`/{entity}`)

Common pattern across Contacts, Companies, Deals, Activities, Tags:

- **Header:** page title + primary action button ("New {Entity}")
- **Filter bar** (Livewire reactive):
  - Free-text search (matches name/email for Contact; name for Company/Deal; subject for Activity)
  - Owner dropdown (Admin/Manager: all users; Sales: self + team)
  - Tag multi-select (optional)
  - Stage/Type dropdown (entity-specific)
  - Date range (created_at)
- **Table view:** sortable columns, pagination (15 per page)
- **Empty state:** icon + message + CTA when no records match
- **Bulk delete:** deferred to polish phase (not in Core MVP)

### Form Pages

- `/{entity}/create`, `/{entity}/{id}/edit`
- Single Livewire component per page (`Create.php` and `Edit.php` reuse same component logic where possible via `#[Modelable]`)
- Validation via `FormRequest` classes invoked from Livewire with `app(\App\Http\Requests\...::class)`
- Inline error messages under each field
- "Save" → success toast → redirect to show page
- "Save & Add Another" (Contact/Activity only) → reset form, success toast

### Show Page (`/{entity}/{id}`)

- **Header:** entity name, status badge (Deal stage), action buttons (Edit, Delete, Log Activity, Add Note)
- **Tabbed sections:**
  - Overview (default — all entity fields + owner info)
  - Activities (timeline, newest first, filterable by type)
  - Notes (list + add form)
  - Related Deals (Contact / Company only)
  - Related Contacts (Company only)
- **Right sidebar:** Owner (with avatar), Tags (clickable to filter), Timestamps (created/updated)
- **Delete:** Admin/Manager can soft-delete any; Sales can soft-delete own only. Confirmation modal.

### Shared Livewire Components

| Component | Purpose | Used In |
|-----------|---------|---------|
| `ActivityTimeline` | Renders polymorphic activities chronologically | Contact/Company/Deal show pages, Dashboard |
| `NoteEditor` | Textarea + submit, no WYSIWYG in MVP | Contact/Company/Deal show pages |
| `TagSelector` | Multi-select with create-new-inline | All create/edit forms |
| `SearchableSelect` | Picker for Company (on Contact), Contact+Company (on Deal) | Deal/Contact forms |
| `OwnerBadge` | User avatar + name, clickable | Listing tables, show pages |
| `StageBadge` | Colored badge per Deal stage | Deal listing & show |

### Routes

```
GET  /                          → dashboard (auth)
GET  /contacts                  → Livewire Contacts\Index
GET  /contacts/create           → Livewire Contacts\Create
GET  /contacts/{id}             → Livewire Contacts\Show
GET  /contacts/{id}/edit        → Livewire Contacts\Edit
# (same pattern for /companies, /deals)
GET  /activities                → Livewire Activities\Index (cross-entity feed)
GET  /tags                      → Livewire Tags\Index
# Auth routes (Laravel Breeze-like, hand-rolled minimal):
GET  /login, POST /login
GET  /register, POST /register
GET  /forgot-password, POST /forgot-password
GET  /reset-password/{token}, POST /reset-password
GET  /verify-email, POST /email/verification-notification
GET  /confirm-password, POST /confirm-password
PUT  /password
GET  /profile, PATCH /profile, DELETE /profile
POST /logout
```

### Tailwind Components Library

Reusable Blade components in `resources/views/components/`:
- `button.blade.php` (variants: primary, secondary, danger, ghost; sizes: sm, md, lg)
- `input.blade.php`, `textarea.blade.php`, `select.blade.php`
- `card.blade.php` (header, body, footer slots)
- `badge.blade.php` (color variants)
- `modal.blade.php` (Alpine-driven)
- `empty-state.blade.php`
- `toast.blade.php` (Alpine store)

---

## 6. Testing, Quality & Verification

### Testing (Pest 3)

**Unit tests** (`tests/Unit/`):
- `TagTest` — slug generation, unique slug conflict resolution
- `DealTest` — stage transitions set `closed_at`, `probability` validation
- `ActivityTest` — type enum, polymorphic type whitelist
- `UserTest` — manager relationship, team scope helper

**Feature tests** (`tests/Feature/`):
- `Auth/{Register,Login,PasswordReset,EmailVerification}Test.php`
- `ProfileTest.php`
- `ContactCrudTest.php` — create, read, update, delete + ownership scoping
- `CompanyCrudTest.php`
- `DealCrudTest.php` — stage transition, value validation, contact+company required
- `ActivityPolymorphicTest.php` — attach to Contact/Company/Deal
- `NotePolymorphicTest.php`
- `TagAttachDetachTest.php`
- `PolicyTest.php` — Sales cannot edit Admin's record; team scope allows same-manager edits
- `DashboardTest.php` — placeholder cards render with auth user

**Database isolation:** every test uses `RefreshDatabase` trait; Factories use `id_ID` Faker locale.

**Factories** (`database/factories/`):
- `UserFactory`, `ContactFactory`, `CompanyFactory`, `DealFactory`, `ActivityFactory`, `NoteFactory`, `TagFactory`
- Realistic data: Indonesian names, common industries, plausible deal values

### Quality Gates

| Tool | Config | Command |
|------|--------|---------|
| Pint | Laravel preset (PSR-12) | `composer pint` (fix) / `composer pint --test` (check) |
| Larastan | Level 5, `app/Models`, `app/Livewire`, `app/Http` analyzed | `composer stan` |
| Pest | Coverage ≥70% | `composer test` |

### CI (`.github/workflows/ci.yml`)

Runs on `push` and `pull_request` to `main`:

1. Checkout, setup PHP 8.3, setup Node 20
2. `composer install --no-interaction`
3. `npm install && npm run build`
4. Start MySQL service, create DB
5. `composer pint --test`
6. `composer stan`
7. `composer test`

All four must pass before merge.

### Verification Gates (Before Declaring Done)

- [ ] `composer install && npm install` succeed fresh
- [ ] `cp .env.example .env && php artisan key:generate && php artisan migrate:fresh --seed` works
- [ ] `npm run dev` and `php artisan serve` both run
- [ ] `composer pint --test` clean
- [ ] `composer stan` clean (level 5)
- [ ] `composer test` all green, coverage ≥70%
- [ ] GitHub Actions CI green
- [ ] Manual smoke test passes:
  1. Register new user
  2. Login as `admin@crm.test`
  3. Create Company "Acme Corp"
  4. Create Contact "Budi" linked to Acme
  5. Create Deal "Q4 Renewal" linked to Budi + Acme, stage=prospecting
  6. Log Activity (type=call) on the Deal
  7. Add Note on the Deal
  8. Attach tag "VIP" to Deal
  9. Logout, login as `sales1@crm.test`, confirm only own records visible
- [ ] README.md exists with setup instructions

---

## 7. Implementation Sequencing

11 sequential phases. Each phase must pass its verification gate before the next begins.

| # | Phase | Deliverable | Verification |
|---|-------|-------------|--------------|
| 1 | Scaffold | Laravel 11 install, Livewire 3, Tailwind, Pint, Stan configs, `.env.example`, `.gitignore`, Vite | `php artisan serve` renders welcome; `npm run dev` runs |
| 2 | DB schema | 7 migrations + factories + seeders (roles, perms, 5 dev users) | `migrate:fresh --seed` succeeds |
| 3 | Models & policies | Eloquent models with relations, 4 policies, Spatie setup | `composer stan` clean; unit tests pass |
| 4 | Auth & layout | Login/register/reset/verify pages, protected `app.blade.php` layout with sidebar | Auth feature tests pass |
| 5 | Contacts CRUD | Livewire Index/Create/Edit/Show + filters + activity timeline + notes | ContactCrudTest + PolicyTest pass; manual smoke |
| 6 | Companies CRUD | Same pattern as Contacts, plus related Contacts tab | CompanyCrudTest pass; manual smoke |
| 7 | Deals CRUD | Same pattern, with Contact+Company picker, stage badge | DealCrudTest pass; manual smoke |
| 8 | Activities | Cross-entity activity log, log-from-show-page, due-soon filter | ActivityPolymorphicTest pass |
| 9 | Notes & Tags | NoteEditor, TagSelector, attach/detach UI | TagAttachDetachTest + NotePolymorphicTest pass |
| 10 | Dashboard | Greeting, 4 placeholder KPI cards, recent activity feed | DashboardTest pass |
| 11 | Polish & CI | Global search, README, GitHub Actions workflow, edge case cleanup | All gates pass; CI green |

### Estimated Effort

~1–3 hours per phase × 11 phases = ~15–30 hours of focused work. Realistic shipping window: 1–2 weeks part-time.

### Deliverables

When Core Foundation is complete:

- Source code committed to `main` branch
- Working local-dev flow documented in README
- All CI checks green
- Ready to brainstorm **Sub-Project #2: Sales Pipeline (Kanban + Drag-Drop)** as the next cycle

---

## 8. Open Questions / Risks

None blocking. Items to revisit at start of each later sub-project:

- **Currency handling:** Core uses IDR default. Multi-currency display (with FX rates) deferred to sub-project #6 (Analytics).
- **Tag color picker:** Core ships with text input for hex. Visual picker deferred.
- **Bulk operations:** Not in Core. Will revisit in polish phase after sub-project #2.
- **Activity reminders/notifications:** Email/Slack reminders deferred to sub-project #5 (Automation).
- **File attachments on Notes/Activities:** Out of scope until email integration sub-project.

---

## 9. Approval

- [x] Scope & roadmap (Section 1)
- [x] Architecture & stack (Section 2)
- [x] Data model (Section 3)
- [x] RBAC (Section 4)
- [x] UI/UX (Section 5)
- [x] Testing & quality (Section 6)
- [x] Implementation sequencing (Section 7)

Status: **Approved for implementation planning** → next step is `writing-plans` skill.
