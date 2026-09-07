# Phase 4 — Auth & Layout

**Date:** 2026-09-07
**Parent spec:** `docs/superpowers/specs/2026-09-06-crm-core-foundation-design.md` §5, §6, §7
**Phase:** 4 of 11
**Goal:** Hand-rolled auth pages (login/register/forgot/reset/verify/confirm), protected `app.blade.php` layout with sidebar + topnav, dashboard skeleton with 4 KPI cards + recent activity. Tailwind 4 components library.

---

## What's already in place from Phase 1–3

- Laravel 12.69 + Livewire 3 + Tailwind 4 + Spatie Permission + Pest 3
- 6 Eloquent models, 4 ownership policies, User with `isAdmin`/`isManager`/`teamIds`
- 5 dev users seeded, 32 permissions
- Larastan level 5 phpstan, Pint, Pest all green

## Deliverables for Phase 4

### Notifications (`app/Notifications/`)

- `VerifyEmailNotification` extends `Illuminate\Auth\Notifications\VerifyEmail` — Bahasa Indonesia mail copy.
- `ResetPasswordNotification` extends `Illuminate\Auth\Notifications\ResetPassword` — Bahasa Indonesia mail copy.
- `User::sendEmailVerificationNotification()` + `sendPasswordResetNotification()` wired to dispatch these subclasses.

### FormRequests (`app/Http/Requests/`)

- `Auth/RegisterRequest` — name, email unique, password + confirmed (Password::defaults()).
- `Auth/LoginRequest` — email, password, remember.
- `Auth/ForgotPasswordRequest` — email.
- `Auth/ResetPasswordRequest` — token, email, password + confirmed.
- `Auth/ConfirmPasswordRequest` — password (authorize requires auth).
- `ProfileUpdateRequest` — name, email unique-ignore-self.
- `PasswordUpdateRequest` — current_password + new password + confirmed.

### Controllers (`app/Http/Controllers/`)

- `HomeController` — redirect auth→dashboard, guest→login.
- `DashboardController` — KPI queries + recent activity feed, scoped to user.
- `ProfileController` — edit/update/password/destroy.
- `Auth/LoginController` — create/store/destroy (logout).
- `Auth/RegisterController` — create/store (dispatches Registered event, auto-login, redirect to verify notice).
- `Auth/VerifyEmailController` — notice/handler (signed URL)/resend (throttled 6/1).
- `Auth/PasswordResetLinkController` — create/store (sends ResetPassword link).
- `Auth/NewPasswordController` — create (form with token)/store (reset password).
- `Auth/ConfirmPasswordController` — show/store.

### Routes (`routes/web.php`)

20 routes added:

```
GET  /                        home           HomeController
GET  /login                   login          LoginController@create
POST /login                   throttle:5,1   LoginController@store
POST /logout                  logout         LoginController@destroy
GET  /register                register       RegisterController@create
POST /register                               RegisterController@store
GET  /forgot-password         password.request  PasswordResetLinkController@create
POST /forgot-password         password.email    PasswordResetLinkController@store
GET  /reset-password/{token}  password.reset    NewPasswordController@create
POST /reset-password          password.store    NewPasswordController@store
GET  /confirm-password        password.confirm  ConfirmPasswordController@show
POST /confirm-password                        ConfirmPasswordController@store
GET  /verify-email            verification.notice   VerifyEmailController@notice
GET  /verify-email/{id}/{hash} signed, throttle:6,1   VerifyEmailController@handler
POST /email/verification-notification throttle:6,1   VerifyEmailController@resend
GET  /profile                 profile.edit        ProfileController@edit
PATCH /profile                profile.update      ProfileController@update
PUT  /profile/password        profile.password.update  ProfileController@updatePassword
DELETE /profile               profile.destroy     ProfileController@destroy
GET  /dashboard               dashboard           DashboardController  (auth + verified)
```

### Layouts (`resources/views/layouts/`)

- `app.blade.php` — sidebar + topnav + main content + toast region. Renders different shell for guest vs authed (`@auth/@else` block yields content). Email-verification banner shown when unverified.
- `guest.blade.php` — centered card for auth pages.
- `partials/sidebar.blade.php` — links per spec §5: Dashboard, Contacts, Companies, Deals, Activities, Tags + Admin section (Users, Roles) when `auth()->user()->isAdmin()`. Entity links are `#` placeholders until Phases 5–9.
- `partials/topnav.blade.php` — global search (disabled, Phase 11) + user dropdown (Alpine.js) with profile + logout.

### Auth views (`resources/views/auth/`)

- `login.blade.php`, `register.blade.php`, `forgot-password.blade.php`, `reset-password.blade.php`, `verify-email.blade.php`, `confirm-password.blade.php`. All Bahasa Indonesia copy. Use guest layout.

### Dashboard view (`resources/views/dashboard.blade.php`)

- Greeting "Selamat datang, {name}".
- 4 KPI cards (placeholder values from scoped DB queries):
  - Pipeline Value (sum deals.value where stage NOT IN won/lost)
  - Deals Won This Month (count where stage=won, month/year matches)
  - Activities Due Today
  - New Contacts This Week
- Recent Activity feed: latest 10, filtered through `ActivityPolicy@view`.

### Profile view (`resources/views/profile/edit.blade.php`)

- Update name/email (re-verifies on email change).
- Update password.
- Delete account (Alpine.js confirm step).

### Tailwind components library (`resources/views/components/`)

| Component | Purpose |
|-----------|---------|
| `button.blade.php` | Variants: primary/secondary/danger/ghost; sizes: sm/md/lg; supports `href`, type=submit/button |
| `input.blade.php` | Label + input + error message; old() value retention |
| `textarea.blade.php` | Same as input |
| `select.blade.php` | With placeholder + options array |
| `card.blade.php` | Optional title, header slot, body slot, footer slot |
| `badge.blade.php` | Color variants: zinc/blue/emerald/amber/red |
| `modal.blade.php` | Alpine.js-driven; listens for `open-modal`/`close-modal` window events |
| `empty-state.blade.php` | Icon + title + description + optional action |
| `toast.blade.php` | Alpine.js store; dispatch via `window.dispatchEvent(new CustomEvent('toast', { detail: { message, type } }))` |

### Assets

- `package.json`: added `alpinejs` dependency.
- `resources/js/app.js`: imports Alpine and starts it.
- `resources/css/app.css`: Tailwind 4 + `[x-cloak]` rule + Notification view sources.

### User model

- Implements `MustVerifyEmail` contract.
- `sendEmailVerificationNotification()` dispatches `VerifyEmailNotification`.
- `sendPasswordResetNotification($token)` dispatches `ResetPasswordNotification`.

## Tests (80 passing)

| File | Count | Coverage |
|------|------:|----------|
| `tests/Feature/Auth/LoginTest` | 6 | login form, valid/invalid creds, guest redirect, logout, validation |
| `tests/Feature/Auth/RegisterTest` | 4 | form, store + Registered event, dup email, password mismatch |
| `tests/Feature/Auth/PasswordResetTest` | 6 | forgot form, send link, non-reveal, reset form, valid/invalid token |
| `tests/Feature/Auth/EmailVerificationTest` | 6 | unverified→notice redirect, notice page, verified→dashboard, signed URL verify, invalid signature, resend |
| `tests/Feature/ProfileTest` | 7 | edit page, guest block, update name/email, clear verified_at on email change, dup email reject, password update, wrong current reject |
| `tests/Feature/DashboardTest` | 4 | guest redirect, KPI cards render, name in greeting, unverified redirect |
| `tests/Feature/ExampleTest` | 2 | updated: home→login (guest), home→dashboard (authed) |
| Phase 1–3 tests | 45 | unchanged |

## Quality gates

- `composer pint --test` — clean
- `composer stan` (level 5, 1G memory) — 0 errors
- `vendor/bin/pest` — 80 passed (144 assertions)
- `npm run build` — succeeds
- `php artisan route:list --except-vendor` — 20 routes registered

## Out of scope (later phases)

- Phase 5–9: Livewire CRUD per entity
- Phase 10: live KPI computation
- Phase 11: global search wiring, README, CI

## Notes for reviewers

- Login throttling uses route-level `throttle:5,1` (no `LoginRequest::ensureIsNotRateLimited()` upgrade yet — covered when login throttling gets more granular in Phase 11 polish).
- VerifyEmail / ResetPassword subclasses keep Laravel's parent mail builder, only override the message lines for Bahasa Indonesia copy.
- Layout uses `@yield('content')` — children extend with `@extends('layouts.app') @section('content')`.
- Alpine via npm + Vite (no CDN). Topnav dropdown + delete-account confirm + modal + toast all Alpine-driven.
- Tailwind 4 uses OKLCH color tokens via `@theme`.