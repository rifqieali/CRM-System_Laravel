<?php

use App\Http\Controllers\Auth\ConfirmPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\VerifyEmailController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ProfileController;
use App\Livewire\Companies\Form as CompanyForm;
use App\Livewire\Companies\Index as CompaniesIndex;
use App\Livewire\Companies\Show as CompanyShow;
use App\Livewire\Contacts\Form as ContactForm;
use App\Livewire\Contacts\Index as ContactsIndex;
use App\Livewire\Contacts\Show as ContactShow;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

// Guest auth routes
Route::middleware('guest')->group(function (): void {
    Route::get('login', [LoginController::class, 'create'])->name('login');
    Route::post('login', [LoginController::class, 'store'])->middleware('throttle:5,1');

    Route::get('register', [RegisterController::class, 'create'])->name('register');
    Route::post('register', [RegisterController::class, 'store']);

    Route::get('forgot-password', [PasswordResetLinkController::class, 'create'])->name('password.request');
    Route::post('forgot-password', [PasswordResetLinkController::class, 'store'])->name('password.email');

    Route::get('reset-password/{token}', [NewPasswordController::class, 'create'])->name('password.reset');
    Route::post('reset-password', [NewPasswordController::class, 'store'])->name('password.store');
});

// Authenticated routes
Route::middleware('auth')->group(function (): void {
    Route::post('logout', [LoginController::class, 'destroy'])->name('logout');

    Route::get('confirm-password', [ConfirmPasswordController::class, 'show'])->name('password.confirm');
    Route::post('confirm-password', [ConfirmPasswordController::class, 'store']);

    // Profile management
    Route::get('profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password.update');
    Route::delete('profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Email verification
Route::middleware(['auth', 'signed'])->group(function (): void {
    Route::get('verify-email/{id}/{hash}', [VerifyEmailController::class, 'handler'])
        ->middleware(['throttle:6,1'])
        ->name('verification.verify');
});

Route::middleware(['auth'])->group(function (): void {
    Route::get('verify-email', [VerifyEmailController::class, 'notice'])
        ->name('verification.notice');

    Route::post('email/verification-notification', [VerifyEmailController::class, 'resend'])
        ->middleware('throttle:6,1')
        ->name('verification.send');
});

// Dashboard (requires auth + verified email)
Route::middleware(['auth', 'verified'])->group(function (): void {
    Route::get('dashboard', DashboardController::class)->name('dashboard');

    Route::get('contacts', ContactsIndex::class)->name('contacts.index');
    Route::get('contacts/create', ContactForm::class)->name('contacts.create');
    Route::get('contacts/{contact}', ContactShow::class)->name('contacts.show');
    Route::get('contacts/{contact}/edit', ContactForm::class)->name('contacts.edit');

    Route::get('companies', CompaniesIndex::class)->name('companies.index');
    Route::get('companies/create', CompanyForm::class)->name('companies.create');
    Route::get('companies/{company}', CompanyShow::class)->name('companies.show');
    Route::get('companies/{company}/edit', CompanyForm::class)->name('companies.edit');
});
