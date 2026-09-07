# Phase 3 — Models & Policies

**Date:** 2026-09-07
**Parent spec:** `docs/superpowers/specs/2026-09-06-crm-core-foundation-design.md` §3, §4, §6
**Phase:** 3 of 11
**Goal:** Eloquent models for all entities, ownership-scoping policies, Spatie wiring, unit + policy tests. No Livewire / routes / UI yet — those land in Phase 4+.

---

## What's already in place from Phase 2

- 7 migrations (`users` CRM fields, `companies`, `contacts`, `deals`, `activities`, `notes`, `tags`, `taggables`, Spatie `permission_tables`).
- 7 factories (User, Company, Contact, Deal, Activity, Note, Tag) all `id_ID` Faker locale.
- `RolePermissionSeeder` (32 permissions, Admin/Manager/Sales roles).
- `UserSeeder` (5 dev users).
- Minimal `User` model with `HasRoles` + `SoftDeletes`, `manager()` relation.

## Deliverables for Phase 3

### Models (`app/Models/`)

1. **Company** — `name/industry/size/website/phone/address/city/country/owner_id/notes`, soft deletes. Relations: `owner()` BelongsTo User, `contacts()` HasMany Contact, `deals()` HasMany Deal, `activities()` `MorphMany`, `notes()` `MorphMany`, `tags()` `morphToMany`.
2. **Contact** — `first_name/last_name/email/phone/mobile/job_title/company_id/owner_id/source/notes`, soft deletes. Relations: `company()` BelongsTo (nullable), `owner()` BelongsTo User, `deals()` HasMany Deal, `activities()` `MorphMany`, `notes()` `MorphMany`, `tags()` `morphToMany`.
3. **Deal** — `name/value/currency/stage/probability/expected_close_date/closed_at/contact_id/company_id/owner_id`, soft deletes. Stage-transition boot sets `closed_at` when stage ∈ {won, lost}. Relations: `contact()` BelongsTo, `company()` BelongsTo, `owner()` BelongsTo User, `activities()` `MorphMany`, `notes()` `MorphMany`, `tags()` `morphToMany`.
4. **Activity** — polymorphic; no soft deletes. Whitelist `activityable_type` to `[Contact, Company, Deal]` via model `creating` hook. Relations: `user()` BelongsTo, `activityable()` `MorphTo`.
5. **Note** — polymorphic; no soft deletes. Whitelist `noteable_type` similarly. Relations: `user()` BelongsTo, `noteable()` `MorphTo`.
6. **Tag** — `name/slug/color`. `slug` auto-generated from name on creating (Str::slug). Relations: `contacts/companies/deals` `morphToMany`.
7. **User** — extend with: `contacts/companies/deals/activities/notes` HasMany (as owner/author), `teamIds()` scope/relation helper for Sales team-scope queries.

### Traits

- **`HasTags`** (`app/Models/Concerns/HasTags.php`) — tiny trait wrapping `morphToMany(Tag::class, 'taggable')` + sync helper. Used by Company/Contact/Deal. Centralizes tag attach API.
- **`HasOwner`** (`app/Models/Concerns/HasOwner.php`) — `owner()` BelongsTo User. Used by Company/Contact/Deal.

### Local scopes (per spec §4, ownership scoping)

- **`ScopedToUser`** trait (`app/Models/Concerns/ScopedToUser.php`) — exposes `scopeScopedTo(Builder $query, User $user)`. Logic:
  - Admin/Manager → no filter (returns query).
  - Sales → `where owner_id = $user->id OR owner_id IN (SELECT id FROM users WHERE manager_id = $user->manager_id)`.
  - If `$user->manager_id` is null, team branch is skipped (sales3 case from seeder).

### Policies (`app/Policies/`)

- **`ContactPolicy`**, **`CompanyPolicy`**, **`DealPolicy`**, **`ActivityPolicy`**.
- Methods: `viewAny`, `view`, `create`, `update`, `delete` (matches permission verbs).
- Logic: Admin/Manager → true; Sales → defer to `OwnedByUser` check using `ScopedToUser` semantics.
- All policies delegate ownership check via shared `AuthorizesOwnedRecord` trait or static helper `Ownership::check($user, $record)`.

### Service / Helper

- **`Ownership` (`app/Support/Ownership.php`)** — `public static function check(User $user, Model $record): bool`. Admin/Manager = true; otherwise `$record->owner_id === $user->id || $record->owner?->manager_id === $user->manager_id`.

### Tests (`tests/`)

- **Unit:**
  - `TagTest` — slug generated, uniqueness collision appends `-2`, `-3`.
  - `DealTest` — transitioning to `won`/`lost` sets `closed_at`; transitioning back clears it.
  - `ActivityTest` — `activityable_type` outside whitelist throws.
  - `UserTest` — `teamIds()` returns users with same `manager_id`.
- **Feature:**
  - `PolicyTest` — Sales cannot edit another sales' record from different manager; sales can edit own; sales can edit teammate's (same manager); Manager/Admin always allowed.

### Quality

- Pint clean, PHPStan level 5 clean.
- Pest all green.

---

## TDD order

1. `TagTest` (slug) → write `Tag` model → green.
2. `DealTest` (stage → closed_at) → write `Deal` model → green.
3. `ActivityTest` (whitelist) → write `Activity` model → green.
4. `UserTest` (teamIds) → extend `User` → green.
5. Models for Company, Contact, Note (no policy logic yet, factories drive tests).
6. `Ownership` helper + `ScopedToUser` trait → unit-testable directly.
7. 4 policies → `PolicyTest` → green.

Each step leaves repo in green state.

---

## File plan (additions only)

```
app/Models/
  Company.php           NEW
  Contact.php           NEW
  Deal.php              NEW
  Activity.php          NEW
  Note.php              NEW
  Tag.php               NEW
  User.php              MODIFY (relations + teamIds)
app/Models/Concerns/
  HasTags.php           NEW
  HasOwner.php          NEW
  ScopedToUser.php      NEW
app/Policies/
  ContactPolicy.php     NEW
  CompanyPolicy.php     NEW
  DealPolicy.php        NEW
  ActivityPolicy.php    NEW
app/Support/
  Ownership.php         NEW
tests/Unit/
  TagTest.php           NEW
  DealTest.php          NEW
  ActivityTest.php      NEW
  UserTest.php          NEW
  OwnershipTest.php     NEW
  ScopedToUserTest.php  NEW
tests/Feature/
  PolicyTest.php        NEW
```

`AppServiceProvider` already auto-discovers policies in Laravel 11+ — no manual registration needed.

---

## Verifications (run after every commit, must stay green)

```bash
composer pint --test
vendor/bin/phpstan analyse
vendor/bin/pest
```

End-of-phase manual: `php artisan migrate:fresh --seed` then in tinker / Pest:

```php
$user = User::where('email', 'sales1@crm.test')->first();
$deals = Deal::scopedTo($user)->get(); // sales1 + sales2 visible, sales3 + admin hidden
```

---

## Out of scope (later phases)

- Phase 4: auth pages, layout, auth middleware.
- Phase 5–9: Livewire CRUD per entity.
- Phase 10: dashboard.
- Phase 11: CI, README, polish.