# CRM System — Core Foundation

Sub-project #1 of 6 for a Laravel 11 CRM. Ships authentication, RBAC, and full CRUD for Contacts, Companies, Deals, Activities, Notes, and Tags. Designed to be extended by sub-projects #2–6 (Sales Pipeline, Custom Fields, Email Integration, Lead Scoring, Analytics).

> **Status:** Sub-Project #1 (Core Foundation) shipped. See `docs/superpowers/specs/2026-09-06-crm-core-foundation-design.md` for the design spec.

---

## Tech stack

- **Framework:** Laravel 11 (PHP 8.3)
- **Frontend:** Livewire 3 + Tailwind CSS 3 + Alpine.js
- **Database:** MySQL 8 (production) / SQLite (local dev)
- **Auth/RBAC:** Spatie Laravel Permission
- **Testing:** Pest 3
- **Quality:** Laravel Pint (PSR-12 preset), Larastan (level 5)
- **CI:** GitHub Actions (`.github/workflows/ci.yml`)

---

## Requirements

- PHP 8.3+ with extensions: `mbstring`, `dom`, `fileinfo`, `mysql` or `sqlite3`
- Node.js 20+
- Composer 2+
- MySQL 8 (production) or SQLite (local dev only)

---

## Setup

### Local development (SQLite, fastest)

```bash
git clone https://github.com/rifqieali/CRM-System_Laravel.git
cd CRM-System_Laravel
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate:fresh --seed
npm install
npm run build
php artisan serve
```

Open <http://localhost:8000>. Login with one of the seeded users below.

### Production-ish (MySQL)

```bash
# 1. Create database & user in MySQL
mysql -u root -p -e "CREATE DATABASE crm CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u root -p -e "CREATE USER 'crm'@'localhost' IDENTIFIED BY 'secret';"
mysql -u root -p -e "GRANT ALL ON crm.* TO 'crm'@'localhost'; FLUSH PRIVILEGES;"

# 2. In .env, set:
#    DB_CONNECTION=mysql
#    DB_HOST=127.0.0.1
#    DB_DATABASE=crm
#    DB_USERNAME=crm
#    DB_PASSWORD=secret

# 3. Run install steps
composer install
npm install
npm run build
php artisan migrate:fresh --seed
```

---

## Seeded dev users

All use password `password`.

| Email | Role | Manager | Notes |
|-------|------|---------|-------|
| `admin@crm.test` | Admin | — | Full access; can manage users & roles |
| `manager@crm.test` | Manager | — | Full record access; no user/role management |
| `sales1@crm.test` | Sales | manager@crm.test | Sees own + `sales2` records (team scope) |
| `sales2@crm.test` | Sales | manager@crm.test | Sees own + `sales1` records (team scope) |
| `sales3@crm.test` | Sales | — | Sees only own records (no manager) |

Ownership-scoping policy: Sales see records where `owner_id = self.id` OR `owner.manager_id = self.manager_id`.

---

## Quality gates

```bash
composer pint:test     # Laravel Pint (PSR-12 style)
composer stan          # Larastan static analysis (level 5)
composer test          # Pest test suite
```

All three must pass before merging. CI runs them on every push and PR to `main`.

---

## Project structure

```
app/
├── Http/Controllers/    # DashboardController, ProfileController
├── Http/Requests/       # FormRequest per entity
├── Livewire/            # Livewire 3 components (Contacts, Companies, Deals, Activities, Notes, Tags, GlobalSearch)
├── Models/              # Eloquent models with ScopedToUser trait
├── Policies/            # Authorization policies per entity
database/
├── factories/           # id_ID locale faker
├── migrations/          # 7 schema migrations
├── seeders/             # RolePermissionSeeder, UserSeeder
resources/views/
├── components/          # Reusable Blade components
├── layouts/             # app.blade.php + partials (sidebar, topnav)
├── livewire/            # Livewire component views
├── errors/              # 404, 403, 500
routes/web.php
tests/Feature/          # Feature tests
tests/Unit/             # Unit tests
.github/workflows/ci.yml
docs/superpowers/specs/ # Design specs
```

---

## Routes

| URL | Handler |
|-----|---------|
| `GET /` | `DashboardController` (auth) |
| `GET /contacts` | `Livewire\Contacts\Index` |
| `GET /contacts/{id}` | `Livewire\Contacts\Show` |
| `GET /companies` | `Livewire\Companies\Index` |
| `GET /deals` | `Livewire\Deals\Index` |
| `GET /activities` | `Livewire\Activities\Index` |
| `GET /notes` | `Livewire\Notes\Index` |
| `GET /tags` | `Livewire\Tags\Index` |
| `GET /profile` | `ProfileController` |
| Auth routes | `/login`, `/register`, `/forgot-password`, `/reset-password/{token}`, `/verify-email` |

Global search lives in the top nav (no dedicated route — `Livewire\GlobalSearch` mounted in `layouts.partials.topnav`).

---

## Locale

Application UI is in Bahasa Indonesia (`APP_LOCALE=id`). All form labels, validation messages, navigation, and dashboard copy are Indonesian. Validation messages translate the most common rules (`required`, `email`, `unique`, `min`, `max`, `confirmed`, `string`, `numeric`, `same`, `between`). Untranslated rule keys fall back to English. Custom translations live in `lang/id/`.

---

## Out of scope (deferred sub-projects)

- Sub-Project #2: Sales Pipeline (Kanban + drag-drop)
- Sub-Project #3: Custom fields & dynamic forms
- Sub-Project #4: Email integration (IMAP/SMTP)
- Sub-Project #5: Lead scoring & automation
- Sub-Project #6: Advanced analytics & forecasting
- Dark mode (deferred to sub-project #2)
- Bulk delete (deferred to first post-#1 sub-project)
- File attachments on Notes/Activities (deferred to sub-project #4)

---

## License

MIT (inherited from Laravel framework).
