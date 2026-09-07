# Phase 2 DB Schema Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Implement all Phase 2 database artifacts (migrations, factories, seeders) for the CRM Core Foundation so `migrate:fresh --seed` works on a fresh clone.

**Architecture:** Standard Laravel migrations with InnoDB-compatible schema (SQLite for local dev and tests, MySQL 8 in production). Factories use `id_ID` Faker locale. Seeders use `firstOrCreate` for idempotency and run roles before users.

**Tech Stack:** Laravel 12, PHP 8.2+, Spatie Laravel Permission 6, Pest 3, Larastan level 5, Pint Laravel preset.

## Global Constraints

- PHP version floor is 8.2 (`composer.json` requires `php: ^8.2`).
- Local dev and test database is SQLite (`database/database.sqlite`); production target is MySQL 8.
- All foreign key columns are indexed.
- Enum columns use string type with validation deferred to models (Phase 3), keeping migrations database-portable.
- Code style must pass `composer pint --test` (Laravel preset).
- Static analysis must pass `composer stan` (Larastan level 5).
- Tests must pass with `vendor/bin/pest`.

---

### Task 1: Users Alteration plus Companies and Contacts Migrations

**Files:**
- Create: `database/migrations/2026_09_06_000001_add_crm_fields_to_users_table.php`
- Create: `database/migrations/2026_09_06_000002_create_companies_table.php`
- Create: `database/migrations/2026_09_06_000003_create_contacts_table.php`
- Test: `database/database.sqlite` via `php artisan migrate`

**Interfaces:**
- Consumes: existing `users` table from `0001_01_01_000000_create_users_table.php`.
- Produces: `users.avatar_path`, `users.manager_id`, `users.deleted_at`, `companies` table, `contacts` table for Task 2 and Task 4.

- [ ] **Step 1: Create users alteration migration**

```bash
php artisan make:migration add_crm_fields_to_users_table --table=users
```

Edit the generated file to this content:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('avatar_path')->nullable()->after('password');
            $table->foreignId('manager_id')->nullable()->after('avatar_path')->constrained('users')->nullOnDelete();
            $table->softDeletes()->after('remember_token');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->dropConstrainedForeignId('manager_id');
            $table->dropColumn('avatar_path');
        });
    }
};
```

- [ ] **Step 2: Create companies migration**

```bash
php artisan make:migration create_companies_table
```

Content:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('name')->index();
            $table->string('industry', 50)->nullable();
            $table->string('size', 20)->nullable();
            $table->string('website')->nullable();
            $table->string('phone', 50)->nullable();
            $table->string('address')->nullable();
            $table->string('city', 100)->nullable();
            $table->string('country', 100)->nullable();
            $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};
```

- [ ] **Step 3: Create contacts migration**

```bash
php artisan make:migration create_contacts_table
```

Content:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contacts', function (Blueprint $table) {
            $table->id();
            $table->string('first_name', 100);
            $table->string('last_name', 100);
            $table->string('email')->index();
            $table->string('phone', 50)->nullable();
            $table->string('mobile', 50)->nullable();
            $table->string('job_title', 150)->nullable();
            $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
            $table->string('source', 50)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contacts');
    }
};
```

- [ ] **Step 4: Run migrate to verify tables create**

Run: `touch database/database.sqlite && php artisan migrate:fresh --env=testing`
Expected: exit 0, output ends with `DONE`, no exceptions.

- [ ] **Step 5: Commit**

```bash
git add database/migrations/2026_09_06_000001_add_crm_fields_to_users_table.php database/migrations/2026_09_06_000002_create_companies_table.php database/migrations/2026_09_06_000003_create_contacts_table.php
git commit -m "feat(phase-02): add users CRM fields, companies and contacts tables"
```

### Task 2: Deals, Activities, Notes, Tags, Taggables Migrations

**Files:**
- Create: `database/migrations/2026_09_06_000004_create_deals_table.php`
- Create: `database/migrations/2026_09_06_000005_create_activities_table.php`
- Create: `database/migrations/2026_09_06_000006_create_notes_table.php`
- Create: `database/migrations/2026_09_06_000007_create_tags_tables.php`
- Test: `php artisan migrate:fresh` green

**Interfaces:**
- Consumes: `users`, `companies`, `contacts` tables from Task 1.
- Produces: `deals`, `activities`, `notes`, `tags`, `taggables` tables for Task 3 and Task 4.

- [ ] **Step 1: Create deals migration**

```bash
php artisan make:migration create_deals_table
```

Content:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('deals', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->decimal('value', 12, 2);
            $table->string('currency', 3)->default('IDR');
            $table->string('stage', 50)->index();
            $table->unsignedTinyInteger('probability')->nullable();
            $table->date('expected_close_date')->nullable()->index();
            $table->timestamp('closed_at')->nullable();
            $table->foreignId('contact_id')->constrained()->cascadeOnDelete();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deals');
    }
};
```

- [ ] **Step 2: Create activities migration**

```bash
php artisan make:migration create_activities_table
```

Content:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activities', function (Blueprint $table) {
            $table->id();
            $table->string('type', 50);
            $table->string('subject');
            $table->text('description')->nullable();
            $table->timestamp('due_at')->nullable()->index();
            $table->timestamp('completed_at')->nullable();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->morphs('activityable');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activities');
    }
};
```

- [ ] **Step 3: Create notes migration**

```bash
php artisan make:migration create_notes_table
```

Content:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notes', function (Blueprint $table) {
            $table->id();
            $table->text('body');
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->morphs('noteable');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notes');
    }
};
```

- [ ] **Step 4: Create tags plus taggables migration**

```bash
php artisan make:migration create_tags_tables
```

Content:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tags', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('slug', 100)->unique();
            $table->string('color', 7)->nullable();
            $table->timestamps();
        });

        Schema::create('taggables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tag_id')->constrained()->cascadeOnDelete();
            $table->morphs('taggable');
            $table->timestamp('created_at')->nullable();
            $table->unique(['tag_id', 'taggable_type', 'taggable_id'], 'taggables_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('taggables');
        Schema::dropIfExists('tags');
    }
};
```

- [ ] **Step 5: Run migrate to verify all tables create**

Run: `php artisan migrate:fresh --env=testing`
Expected: exit 0, all tables present in `sqlite3 database/database.sqlite .tables` or via `php artisan tinker --execute="echo implode(',', array_keys(Schema::getConnection()->getDoctrineSchemaManager()->listTables()));"`.

- [ ] **Step 6: Commit**

```bash
git add database/migrations/2026_09_06_000004_create_deals_table.php database/migrations/2026_09_06_000005_create_activities_table.php database/migrations/2026_09_06_000006_create_notes_table.php database/migrations/2026_09_06_000007_create_tags_tables.php
git commit -m "feat(phase-02): add deals, activities, notes, tags tables"
```

### Task 3: Factories for All Entities

**Files:**
- Modify: `database/factories/UserFactory.php`
- Create: `database/factories/CompanyFactory.php`
- Create: `database/factories/ContactFactory.php`
- Create: `database/factories/DealFactory.php`
- Create: `database/factories/ActivityFactory.php`
- Create: `database/factories/NoteFactory.php`
- Create: `database/factories/TagFactory.php`
- Test: `tests/Feature/Phase02SmokeTest.php` (temporary, deleted after verification or kept as seed smoke test)

**Interfaces:**
- Consumes: tables from Task 1 and Task 2. Models do not exist yet (Phase 3), so factories reference `App\Models\X` class names that resolve once Phase 3 lands; for Phase 2 verification, factories are validated via seeder run and tinker, not via model tests.
- Produces: factory definitions used by Task 4 seeders and Phase 3 tests.

- [ ] **Step 1: Extend UserFactory with CRM fields**

Append to `definition()` return array in `database/factories/UserFactory.php`:

```php
'avatar_path' => null,
'manager_id' => null,
```

Add Faker locale note: factories use the application faker locale; set `APP_FAKER_LOCALE=id_ID` in `.env.example` if missing.

- [ ] **Step 2: Create Company, Contact, Deal factories**

`database/factories/CompanyFactory.php`:

```php
<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Company>
 */
class CompanyFactory extends Factory
{
    protected $model = Company::class;

    public function definition(): array
    {
        return [
            'name' => fake('id_ID')->company(),
            'industry' => fake()->randomElement(['tech', 'finance', 'healthcare', 'retail', 'manufacturing', 'other']),
            'size' => fake()->randomElement(['1-10', '11-50', '51-200', '201-500', '500+']),
            'website' => fake()->domainName(),
            'phone' => fake('id_ID')->phoneNumber(),
            'address' => fake('id_ID')->streetAddress(),
            'city' => fake('id_ID')->city(),
            'country' => 'Indonesia',
            'owner_id' => User::factory(),
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
```

`database/factories/ContactFactory.php`:

```php
<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Contact;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Contact>
 */
class ContactFactory extends Factory
{
    protected $model = Contact::class;

    public function definition(): array
    {
        return [
            'first_name' => fake('id_ID')->firstName(),
            'last_name' => fake('id_ID')->lastName(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake('id_ID')->phoneNumber(),
            'mobile' => fake('id_ID')->phoneNumber(),
            'job_title' => fake()->jobTitle(),
            'company_id' => Company::factory(),
            'owner_id' => User::factory(),
            'source' => fake()->randomElement(['website', 'referral', 'cold-call', 'event', 'other']),
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
```

`database/factories/DealFactory.php`:

```php
<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Contact;
use App\Models\Deal;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Deal>
 */
class DealFactory extends Factory
{
    protected $model = Deal::class;

    public function definition(): array
    {
        return [
            'name' => fake()->words(3, true),
            'value' => fake()->randomFloat(2, 1000000, 500000000),
            'currency' => 'IDR',
            'stage' => fake()->randomElement(['prospecting', 'qualification', 'proposal', 'negotiation', 'won', 'lost']),
            'probability' => fake()->optional()->numberBetween(0, 100),
            'expected_close_date' => fake()->optional()->date(),
            'closed_at' => null,
            'contact_id' => Contact::factory(),
            'company_id' => Company::factory(),
            'owner_id' => User::factory(),
        ];
    }
}
```

- [ ] **Step 3: Create Activity, Note, Tag factories**

`database/factories/ActivityFactory.php`:

```php
<?php

namespace Database\Factories;

use App\Models\Activity;
use App\Models\Contact;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Activity>
 */
class ActivityFactory extends Factory
{
    protected $model = Activity::class;

    public function definition(): array
    {
        return [
            'type' => fake()->randomElement(['call', 'email', 'meeting', 'task']),
            'subject' => fake()->sentence(4),
            'description' => fake()->optional()->paragraph(),
            'due_at' => fake()->optional()->dateTime(),
            'completed_at' => null,
            'user_id' => User::factory(),
            'activityable_type' => Contact::class,
            'activityable_id' => Contact::factory(),
        ];
    }
}
```

`database/factories/NoteFactory.php`:

```php
<?php

namespace Database\Factories;

use App\Models\Contact;
use App\Models\Note;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Note>
 */
class NoteFactory extends Factory
{
    protected $model = Note::class;

    public function definition(): array
    {
        return [
            'body' => fake()->paragraph(),
            'user_id' => User::factory(),
            'noteable_type' => Contact::class,
            'noteable_id' => Contact::factory(),
        ];
    }
}
```

`database/factories/TagFactory.php`:

```php
<?php

namespace Database\Factories;

use App\Models\Tag;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Tag>
 */
class TagFactory extends Factory
{
    protected $model = Tag::class;

    public function definition(): array
    {
        $name = fake()->unique()->word();

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'color' => fake()->optional()->hexColor(),
        ];
    }
}
```

- [ ] **Step 4: Commit factories**

```bash
git add database/factories/
git commit -m "feat(phase-02): add factories for all CRM entities"
```

Note: factories reference models landing in Phase 3. Larastan may flag missing classes until Phase 3; this is expected and resolved in Phase 3. Pint must still pass.

### Task 4: RolePermissionSeeder, UserSeeder, DatabaseSeeder Wiring

**Files:**
- Create: `database/seeders/RolePermissionSeeder.php`
- Create: `database/seeders/UserSeeder.php`
- Modify: `database/seeders/DatabaseSeeder.php`
- Test: `php artisan migrate:fresh --seed`

**Interfaces:**
- Consumes: Spatie permission tables (Phase 1), `users` table with `manager_id` (Task 1), factories (Task 3).
- Produces: 3 roles, 38 permissions, 5 dev users with roles assigned.

- [ ] **Step 1: Create RolePermissionSeeder**

```bash
php artisan make:seeder RolePermissionSeeder
```

Content:

```php
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $entities = ['contact', 'company', 'deal', 'activity', 'note', 'tag'];
        $actions = ['view-any', 'view', 'create', 'update', 'delete'];

        $permissions = [];
        foreach ($entities as $entity) {
            foreach ($actions as $action) {
                $permissions[] = "{$action}-{$entity}";
            }
        }
        $permissions[] = 'manage-users';
        $permissions[] = 'manage-roles';

        foreach ($permissions as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        $admin = Role::firstOrCreate(['name' => 'Admin', 'guard_name' => 'web']);
        $admin->syncPermissions(Permission::all());

        $manager = Role::firstOrCreate(['name' => 'Manager', 'guard_name' => 'web']);
        $manager->syncPermissions(Permission::whereNotIn('name', ['manage-users', 'manage-roles'])->get());

        $sales = Role::firstOrCreate(['name' => 'Sales', 'guard_name' => 'web']);
        $sales->syncPermissions(Permission::whereNotIn('name', ['manage-users', 'manage-roles'])->get());
    }
}
```

- [ ] **Step 2: Create UserSeeder**

```bash
php artisan make:seeder UserSeeder
```

Content:

```php
<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $manager = User::firstOrCreate(
            ['email' => 'manager@crm.test'],
            ['name' => 'Manager', 'password' => Hash::make('password'), 'email_verified_at' => now()]
        );
        $manager->assignRole('Manager');

        $admin = User::firstOrCreate(
            ['email' => 'admin@crm.test'],
            ['name' => 'Admin', 'password' => Hash::make('password'), 'email_verified_at' => now()]
        );
        $admin->assignRole('Admin');

        foreach (['sales1', 'sales2'] as $name) {
            $user = User::firstOrCreate(
                ['email' => "{$name}@crm.test"],
                ['name' => ucfirst($name), 'password' => Hash::make('password'), 'email_verified_at' => now(), 'manager_id' => $manager->id]
            );
            $user->assignRole('Sales');
        }

        $sales3 = User::firstOrCreate(
            ['email' => 'sales3@crm.test'],
            ['name' => 'Sales3', 'password' => Hash::make('password'), 'email_verified_at' => now()]
        );
        $sales3->assignRole('Sales');
    }
}
```

- [ ] **Step 3: Wire DatabaseSeeder**

Replace `database/seeders/DatabaseSeeder.php` `run()` body with:

```php
$this->call([
    RolePermissionSeeder::class,
    UserSeeder::class,
]);
```

- [ ] **Step 4: Run seed to verify it passes**

Run: `php artisan migrate:fresh --seed --env=testing`
Expected: exit 0. Verify with: `php artisan tinker --execute="echo \App\Models\User::count();" --env=testing` outputs `5` once Phase 3 models exist; for Phase 2, verify via `sqlite3 database/database.sqlite "select count(*) from users;"` outputs `5` and `"select count(*) from permissions;"` outputs `32`.

Note: `UserSeeder` uses `App\Models\User` which already exists in Laravel 12 scaffold, so Phase 2 seeding works without Phase 3 models. Factory files referencing missing models are not loaded during seeding.

- [ ] **Step 5: Commit**

```bash
git add database/seeders/
git commit -m "feat(phase-02): add roles, permissions and dev users seeders"
```

### Task 5: Quality Gates and Push

**Files:**
- Modify: none (verification only)

**Interfaces:**
- Consumes: all Phase 2 artifacts.
- Produces: green gates, pushed branch ready for PR.

- [ ] **Step 1: Run Pint check**

Run: `composer pint --test`
Expected: PASS, no style errors.

- [ ] **Step 2: Run Larastan**

Run: `composer stan`
Expected: no errors. If factories referencing Phase 3 models error, exclude is not allowed; instead the error documents Phase 3 dependency and must show only missing-model errors, zero errors in migrations and seeders.

- [ ] **Step 3: Run test suite**

Run: `vendor/bin/pest`
Expected: existing 2 tests pass.

- [ ] **Step 4: Push branch**

```bash
git push -u origin phase/02-db
```

- [ ] **Step 5: Open PR with gh**

```bash
gh pr create --title "[Phase 2] Migrations + factories + seeders" --body "Closes #3. Part of #1. ..." --base main --head phase/02-db
```

## Self-Review

- Spec coverage: Section 3 tables all mapped to Task 1 and Task 2. Section 4 roles, permissions, and 5 dev users mapped to Task 4. Factories mapped to Task 3. Verification gates mapped to Task 5.
- Placeholder scan: no TBD, TODO, or vague validation. All migration columns explicit. All seeder emails and roles explicit.
- Type consistency: `owner_id` always `foreignId` constrained to `users` with cascade on delete. Morph columns use Laravel `morphs()` helper producing `{name}_type` string and `{name}_id` unsigned big integer. Permission names follow `{action}-{entity}` singular pattern matching spec Section 4.
