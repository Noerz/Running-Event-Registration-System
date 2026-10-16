<?php

use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminEventController;
use App\Http\Controllers\Admin\AdminParticipantController;
use App\Http\Controllers\Admin\AdminRaceCategoryController;
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
        Route::get('dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');

        Route::get('participants', [AdminParticipantController::class, 'index'])->name('participants.index');
        Route::get('participants/{participant}', [AdminParticipantController::class, 'show'])->name('participants.show');
        Route::get('participants/{participant}/edit', [AdminParticipantController::class, 'edit'])->name('participants.edit');
        Route::put('participants/{participant}', [AdminParticipantController::class, 'update'])->name('participants.update');
        Route::get('participants/{participant}/payment-proof', [AdminParticipantController::class, 'paymentProof'])
            ->name('participants.payment-proof');

        Route::post('participants/{participant}/approve', [ParticipantVerificationController::class, 'approve'])
            ->name('participants.approve');
        Route::post('participants/{participant}/reject', [ParticipantVerificationController::class, 'reject'])
            ->name('participants.reject');
        Route::post('participants/{participant}/resubmit-proof', [ParticipantVerificationController::class, 'resubmitProof'])
            ->name('participants.resubmit-proof');

        Route::resource('events', AdminEventController::class)
            ->except(['show'])
            ->names('events')
            ->parameters(['events' => 'event']);

        Route::resource('race-categories', AdminRaceCategoryController::class)
            ->except(['show'])
            ->names('race-categories')
            ->parameters(['race-categories' => 'raceCategory']);

        Route::resource('users', AdminUserController::class)
            ->except(['show'])
            ->names('users')
            ->parameters(['users' => 'adminUser']);
    });
});
