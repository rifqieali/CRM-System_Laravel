# Phase 11 — Polish & CI

**Date:** 2026-09-07
**Parent spec:** `docs/superpowers/specs/2026-09-06-crm-core-foundation-design.md` §5 (global search), §6 (CI workflow, quality gates), §7 row 11
**Phase:** 11 of 11 (final)
**Goal:** Wire global search, ship GitHub Actions CI, replace default Laravel README with project-specific setup, fix remaining polish items (Indonesian locale for validation messages, custom error pages, search empty state).

---

## What was already in place

Phases 1–10 shipped a working Core Foundation: full CRUD for Contact/Company/Deal/Activity/Note/Tag, dashboard, hand-rolled auth, RBAC, ownership-scoped queries. Two pieces of `§5` were left as stubs:

- `resources/views/layouts/partials/topnav.blade.php` has the global search input rendered with `disabled` (placeholder only).
- No `.github/workflows/ci.yml` exists — CI is mentioned in `§6` but never written.
- `README.md` is the default Laravel 11 stub (5 pages about Laravel itself, nothing about this app).

Plus three polish items the spec implicitly assumes but never explicitly listed:

- `APP_LOCALE=en` while every user-facing string in the UI is Bahasa Indonesia ("Selamat pagi", "Anda punya", "Cari"). Validation errors currently flash in English. Spec doesn't say "add Indonesian locale" but a user-facing Indonesian app with English error messages is a clear polish miss.
- No `resources/views/errors/{404,403,500}.blade.php`. Laravel ships fallback pages but they're unbranded.
- Search empty-state UI not specified — when a search returns 0 results, just a blank dropdown.

---

## Deliverables for Phase 11

### 1. Global search

**New Livewire component** `app/Livewire/GlobalSearch.php` (Volt-less, classic Livewire 3 single-file component):

- Mounted in `resources/views/layouts/partials/topnav.blade.php` as `<livewire:global-search />` (replaces the disabled `<input type="search">`).
- Public properties: `string $query = ''`; protected `array $results = []`.
- `updatedQuery()` debounced 300ms — on each meaningful change, runs the search and populates `$results`.
- Search shape (always scoped to current user via `ScopedToUser`):
  - `Contact::scopedTo($user)->where(fn($q) => $q->where('first_name', 'like', "%{$query}%")->orWhere('last_name', 'like', "%{$query}%")->orWhere('email', 'like', "%{$query}%"))->limit(5)->get()`
  - `Company::scopedTo($user)->where('name', 'like', "%{$query}%")->limit(5)->get()`
  - `Deal::scopedTo($user)->where('name', 'like', "%{$query}%")->limit(5)->get()`
- Each result tagged with its morph class and route. Dropdown renders three sections (Contacts, Companies, Deals) with link to each show page. Empty results show "Tidak ada hasil untuk "{query}"".
- Alpine `x-on:click.outside` closes the dropdown. Keyboard: Escape clears focus.
- Min query length 2 chars to avoid hammering the DB on every keystroke.

**New feature test** `tests/Feature/GlobalSearchTest.php` (~6 tests):

- `it renders search input in topnav` — assertSee `Cari contacts, companies, deals...`
- `it searches contacts by first name, last name, and email`
- `it searches companies by name`
- `it searches deals by name`
- `it scopes search results to user` (cross-team Sales blocked)
- `it shows empty state for queries with no results` — assertSee `Tidak ada hasil`
- `it ignores queries shorter than 2 characters`

### 2. GitHub Actions CI

**New file** `.github/workflows/ci.yml`. Triggers on `push` and `pull_request` to `main`. Steps per spec §6:

1. `actions/checkout@v4`
2. `shivammathur/setup-php@v2` with PHP 8.3, extensions: `mbstring, dom, fileinfo, mysql, sqlite`
3. `actions/setup-node@v4` with Node 20, cache: `npm`
4. `composer install --no-interaction --prefer-dist`
5. `npm install && npm run build`
6. `cp .env.example .env`
7. `mysql --version` then create DB (using `services.mysql` block, MySQL 8 image, env `MYSQL_ALLOW_EMPTY_PASSWORD: yes`, `MYSQL_DATABASE: crm_test`)
8. Set `DB_CONNECTION=mysql`, `DB_HOST=127.0.0.1`, `DB_DATABASE=crm_test`, `DB_USERNAME=root`, `DB_PASSWORD=` via env in the `run:` block (NOT committed `.env`)
9. `php artisan key:generate`
10. `php artisan migrate:fresh --seed`
11. `composer pint --test`
12. `composer stan`
13. `composer test`

Use the same composer scripts defined in `composer.json` (already exists: `pint`, `stan`, `test`).

Note: Project's local dev uses SQLite for speed. CI uses MySQL 8 to match spec §2 stack. Tests must pass on both — they already do (we verified MySQL during Phase 2 smoke).

### 3. README

**Rewrite** `README.md`. Sections:

- Project name + one-liner purpose
- Tech stack (link to spec)
- Requirements (PHP 8.3, Node 20, Composer, MySQL 8 or SQLite)
- Setup steps: `cp .env.example .env` → `php artisan key:generate` → `php artisan migrate:fresh --seed` → `npm install && npm run build` → `php artisan serve`
- Dev users (from `UserSeeder`): table of email/role/manager
- Quality gates: `composer pint`, `composer stan`, `composer test`
- Project structure (trimmed tree)
- Specs index (link to `docs/superpowers/specs/2026-09-06-crm-core-foundation-design.md`)
- License (MIT, inherited from Laravel)

### 4. Edge case cleanup

**a. Indonesian locale** (`.env.example`):
- `APP_LOCALE=id` (was `en`)
- `APP_FALLBACK_LOCALE=en` (keep — Laravel framework strings stay English)
- `APP_FAKER_LOCALE=id_ID` (was `en_US`) — factories use Indonesian names

**New files** `lang/id/validation.php` and `lang/id/auth.php` with Indonesian overrides for common rules. Pattern: copy the English `lang/en/*.php` structure from `vendor/laravel/framework/src/Illuminate/Translation/lang/en/` then translate. (No need to add all rules — Laravel will fall back to English for any key not overridden.)

**b. Custom error pages**:
- `resources/views/errors/404.blade.php` — branded with app logo + "Halaman tidak ditemukan" + back link to dashboard
- `resources/views/errors/403.blade.php` — "Anda tidak punya akses ke halaman ini" + back link
- `resources/views/errors/500.blade.php` — generic "Terjadi kesalahan" + back link
- All three extend `layouts.app` (so user dropdown still works), use the same Tailwind palette

**c. Empty state for search** — already covered in `GlobalSearch` component above.

---

## Quality gates

- `composer pint --test` — clean
- `composer stan` (level 5) — 0 errors
- `vendor/bin/pest` — 207+ tests green (was 201 baseline + ~6 new search tests + ~3 new auth tests for Indonesian validation messages if they fail under en; net ~207)

## Out of scope (deferred sub-projects)

- Real KPI computation, charts, forecasting (sub-project #6)
- Kanban board (sub-project #2)
- Custom field schema (sub-project #3)
- Email integration (sub-project #4)
- Lead scoring (sub-project #5)
- Dark mode (spec §5 says deferred to sub-project #2)
- Bulk delete (spec §5 says deferred to polish phase → re-defer to first post-#1 sub-project)
- File uploads on Notes/Activities (deferred to email integration sub-project)

## Notes for reviewers

- Global search returns max 15 results (5 per entity). Not paginated. For a Core Foundation with ~100s of records, this is fine. A "View all results" link to a dedicated search route is out of scope (no spec row covers it).
- Indonesian validation messages are additive: any key not in `lang/id/validation.php` falls back to English. This means coverage is partial but the most common ones (`required`, `email`, `unique`, `min`, `max`, `confirmed`) are translated.
- CI uses MySQL 8 per spec §6. SQLite is faster locally but doesn't match the production target. The `RefreshDatabase` trait means tests run on whatever connection `phpunit.xml` points to.
- 404/403/500 pages extend `layouts.app` so the user keeps their session and dropdown works. This is intentional (better UX than Laravel's default which uses `layout` and resets everything).
- The `APP_FAKER_LOCALE=id_ID` change means new factories generate Indonesian names. Existing data (already seeded in dev DBs) keeps its names. No migration script needed.

## Definition of done

Phase 11 done = ALL of:

- [ ] `GlobalSearch` Livewire component ships, topnav search works end-to-end
- [ ] `GlobalSearchTest` 6+ tests pass
- [ ] `lang/id/validation.php` + `lang/id/auth.php` exist and translate at least the 8 most common rules
- [ ] `APP_LOCALE=id` in `.env.example`
- [ ] `resources/views/errors/{404,403,500}.blade.php` exist and render branded
- [ ] `.github/workflows/ci.yml` exists with the 4 quality gates + MySQL service
- [ ] `README.md` is project-specific (no Laravel stub)
- [ ] `composer pint --test` clean
- [ ] `composer stan` clean
- [ ] `vendor/bin/pest` 207+ green
- [ ] Local smoke: login as `admin@crm.test`, search "Acme" in topnav, see Acme Corp in dropdown, click → company show page
- [ ] Branch pushed, PR opened, references parent issue #1

## Risk register

| Risk | Likelihood | Mitigation |
|------|------------|------------|
| MySQL CI service slower than SQLite (test runtime 2x) | Medium | Acceptable; CI runtime ~3 min still within GitHub free tier. If blocks, switch to SQLite-in-memory. |
| Indonesian locale breaks an existing English-language test assertion | Low | Audit tests for `assertSee('email', ...)` style assertions after locale change; update to `'alamat email'` or whatever the Indonesian equivalent is. |
| `GlobalSearch` debounce conflicts with Livewire 3 default | Low | Use `updatedQuery()` with `#[On('debounce')]` listener; or Livewire 3's built-in `wire:model.live.debounce.300ms`. Verify in test that 300ms delay actually debounces. |
| Vite manifest missing in CI | Already handled | `npm run build` before tests |
| Coverage drops below 70% | Low | New GlobalSearch component + tests will add coverage net positive |

## Implementation order

1. Indonesian locale (small, isolated, doesn't affect other PRs)
2. Error pages (small, isolated)
3. Global search (medium, main feature)
4. README (small, docs only)
5. CI workflow (small, infra only)
6. Final verification (pint + stan + pest)
7. Commit + push + issue + PR
