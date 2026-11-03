<?php

use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminEventController;
use App\Http\Controllers\Admin\AdminParticipantController;
use App\Http\Controllers\Admin\AdminPaymentSettingController;
use App\Http\Controllers\Admin\AdminRaceCategoryController;
use App\Http\Controllers\Admin\AdminRoleController;
use App\Http\Controllers\Admin\AdminSiteSettingController;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Admin\Auth\AdminLoginController;
use App\Http\Controllers\Admin\ParticipantVerificationController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\RegistrationController;
use App\Http\Controllers\StatusCheckController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public / User
|--------------------------------------------------------------------------
*/

Route::get('/', [HomeController::class, 'index'])->name('home');

Route::get('/register', [RegistrationController::class, 'create'])->name('register.create');
Route::post('/register', [RegistrationController::class, 'store'])->name('register.store');

Route::get('/registration/success/{registrationCode}', [RegistrationController::class, 'success'])
    ->middleware('signed')
    ->name('registration.success');

Route::get('/check-registration', [StatusCheckController::class, 'show'])->name('check.show');
Route::post('/check-registration', [StatusCheckController::class, 'check'])
    ->middleware('throttle:status-check')
    ->name('check.check');

/*
|--------------------------------------------------------------------------
| Admin
|--------------------------------------------------------------------------
*/

Route::prefix('admin')->name('admin.')->group(function () {
    Route::middleware('guest')->group(function () {
        Route::get('login', [AdminLoginController::class, 'create'])->name('login');
        // Rate limiting login dikelola di AdminLoginService (lockout per email+IP).
        Route::post('login', [AdminLoginController::class, 'store'])->name('login.attempt');
    });

    Route::post('logout', [AdminLoginController::class, 'destroy'])
        ->middleware('auth')
        ->name('logout');

    Route::middleware(['auth', 'can:access-admin'])->group(function () {
        Route::get('dashboard', [AdminDashboardController::class, 'index'])
            ->middleware('permission:dashboard.view')
            ->name('dashboard');

        Route::get('participants', [AdminParticipantController::class, 'index'])
            ->middleware('permission:participants.view')
            ->name('participants.index');
        Route::get('participants/{participant}', [AdminParticipantController::class, 'show'])
            ->middleware('permission:participants.view')
            ->name('participants.show');
        Route::get('participants/{participant}/edit', [AdminParticipantController::class, 'edit'])
            ->middleware('permission:participants.update')
            ->name('participants.edit');
        Route::put('participants/{participant}', [AdminParticipantController::class, 'update'])
            ->middleware('permission:participants.update')
            ->name('participants.update');
        Route::delete('participants/{participant}', [AdminParticipantController::class, 'destroy'])
            ->middleware('permission:participants.delete')
            ->name('participants.destroy');
        Route::get('participants/{participant}/payment-proof', [AdminParticipantController::class, 'paymentProof'])
            ->middleware('permission:participants.view')
            ->name('participants.payment-proof');

        Route::post('participants/{participant}/approve', [ParticipantVerificationController::class, 'approve'])
            ->middleware('permission:participants.verify')
            ->name('participants.approve');
        Route::post('participants/{participant}/reject', [ParticipantVerificationController::class, 'reject'])
            ->middleware('permission:participants.verify')
            ->name('participants.reject');
        Route::post('participants/{participant}/resubmit-proof', [ParticipantVerificationController::class, 'resubmitProof'])
            ->middleware('permission:participants.verify')
            ->name('participants.resubmit-proof');

        Route::get('events', [AdminEventController::class, 'index'])
            ->middleware('permission:events.view')->name('events.index');
        Route::get('events/create', [AdminEventController::class, 'create'])
            ->middleware('permission:events.create')->name('events.create');
        Route::post('events', [AdminEventController::class, 'store'])
            ->middleware('permission:events.create')->name('events.store');
        Route::get('events/{event}/edit', [AdminEventController::class, 'edit'])
            ->middleware('permission:events.update')->name('events.edit');
        Route::put('events/{event}', [AdminEventController::class, 'update'])
            ->middleware('permission:events.update')->name('events.update');
        Route::delete('events/{event}', [AdminEventController::class, 'destroy'])
            ->middleware('permission:events.delete')->name('events.destroy');

        Route::get('race-categories', [AdminRaceCategoryController::class, 'index'])
            ->middleware('permission:race_categories.view')->name('race-categories.index');
        Route::get('race-categories/create', [AdminRaceCategoryController::class, 'create'])
            ->middleware('permission:race_categories.create')->name('race-categories.create');
        Route::post('race-categories', [AdminRaceCategoryController::class, 'store'])
            ->middleware('permission:race_categories.create')->name('race-categories.store');
        Route::get('race-categories/{raceCategory}/edit', [AdminRaceCategoryController::class, 'edit'])
            ->middleware('permission:race_categories.update')->name('race-categories.edit');
        Route::put('race-categories/{raceCategory}', [AdminRaceCategoryController::class, 'update'])
            ->middleware('permission:race_categories.update')->name('race-categories.update');
        Route::delete('race-categories/{raceCategory}', [AdminRaceCategoryController::class, 'destroy'])
            ->middleware('permission:race_categories.delete')->name('race-categories.destroy');

        Route::get('payment-settings', [AdminPaymentSettingController::class, 'index'])
            ->middleware('permission:payment_settings.view')->name('payment-settings.index');
        Route::post('payment-settings/bank-accounts', [AdminPaymentSettingController::class, 'storeBank'])
            ->middleware('permission:payment_settings.update')->name('payment-settings.bank.store');
        Route::put('payment-settings/bank-accounts/{bankAccount}', [AdminPaymentSettingController::class, 'updateBank'])
            ->middleware('permission:payment_settings.update')->name('payment-settings.bank.update');
        Route::delete('payment-settings/bank-accounts/{bankAccount}', [AdminPaymentSettingController::class, 'destroyBank'])
            ->middleware('permission:payment_settings.update')->name('payment-settings.bank.destroy');
        Route::post('payment-settings/qris', [AdminPaymentSettingController::class, 'updateQris'])
            ->middleware('permission:payment_settings.update')->name('payment-settings.qris.update');
        Route::delete('payment-settings/qris', [AdminPaymentSettingController::class, 'destroyQris'])
            ->middleware('permission:payment_settings.update')->name('payment-settings.qris.destroy');

        Route::get('site-settings', [AdminSiteSettingController::class, 'index'])
            ->middleware('permission:payment_settings.view')->name('site-settings.index');
        Route::post('site-settings/hero-image', [AdminSiteSettingController::class, 'updateHeroImage'])
            ->middleware('permission:payment_settings.update')->name('site-settings.hero-image.update');
        Route::delete('site-settings/hero-image', [AdminSiteSettingController::class, 'destroyHeroImage'])
            ->middleware('permission:payment_settings.update')->name('site-settings.hero-image.destroy');

        Route::get('users', [AdminUserController::class, 'index'])
            ->middleware('permission:users.view')->name('users.index');
        Route::get('users/create', [AdminUserController::class, 'create'])
            ->middleware('permission:users.create')->name('users.create');
        Route::post('users', [AdminUserController::class, 'store'])
            ->middleware('permission:users.create')->name('users.store');
        Route::get('users/{adminUser}/edit', [AdminUserController::class, 'edit'])
            ->middleware('permission:users.update')->name('users.edit');
        Route::put('users/{adminUser}', [AdminUserController::class, 'update'])
            ->middleware('permission:users.update')->name('users.update');
        Route::delete('users/{adminUser}', [AdminUserController::class, 'destroy'])
            ->middleware('permission:users.delete')->name('users.destroy');

        Route::get('roles', [AdminRoleController::class, 'index'])
            ->middleware('permission:roles.view')->name('roles.index');
        Route::get('roles/create', [AdminRoleController::class, 'create'])
            ->middleware('permission:roles.create')->name('roles.create');
        Route::post('roles', [AdminRoleController::class, 'store'])
            ->middleware('permission:roles.create')->name('roles.store');
        Route::get('roles/{role}/edit', [AdminRoleController::class, 'edit'])
            ->middleware('permission:roles.update')->name('roles.edit');
        Route::put('roles/{role}', [AdminRoleController::class, 'update'])
            ->middleware('permission:roles.update')->name('roles.update');
        Route::delete('roles/{role}', [AdminRoleController::class, 'destroy'])
            ->middleware('permission:roles.delete')->name('roles.destroy');
        Route::post('roles/{role}/toggle', [AdminRoleController::class, 'toggle'])
            ->middleware('permission:roles.update')->name('roles.toggle');
    });
});
