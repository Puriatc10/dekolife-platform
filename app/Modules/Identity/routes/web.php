<?php

declare(strict_types=1);

use App\Modules\Identity\Http\Controllers\Account\ProfileController;
use App\Modules\Identity\Http\Controllers\Auth\LoginController;
use App\Modules\Identity\Http\Controllers\Auth\PasswordController;
use App\Modules\Identity\Http\Controllers\Auth\PasswordResetController;
use App\Modules\Identity\Http\Controllers\Auth\RegisterController;
use App\Modules\Identity\Http\Controllers\PanelController;
use Illuminate\Support\Facades\Route;

/*
 * Authentication and the four panels.
 *
 * `active-role` both checks the active role and asserts that the requested panel belongs
 * to it, so /store cannot be opened while the marketer role is active (NEG-IAM-03).
 * `password.changed` gates every panel until a temporary password is replaced
 * (پیوست ۶ بند ۸).
 */
Route::middleware(['web', 'guest'])->group(function (): void {
    Route::get('login', [LoginController::class, 'show'])->name('login');
    Route::post('login', [LoginController::class, 'store'])
        ->middleware('throttle:10,1')
        ->name('login.store');

    // Registration: mobile -> code -> account (REQ-IAM-001). The mobile lives in the
    // session between steps, so a bookmarked verify URL without a session goes back to step 1.
    Route::get('register', [RegisterController::class, 'show'])->name('register');
    Route::post('register', [RegisterController::class, 'requestCode'])
        ->middleware('throttle:6,1')
        ->name('register.request');
    Route::get('register/verify', [RegisterController::class, 'showVerify'])->name('register.verify');
    Route::post('register/verify', [RegisterController::class, 'verify'])
        ->middleware('throttle:10,1')
        ->name('register.verify.store');
    Route::post('register/resend', [RegisterController::class, 'resend'])
        ->middleware('throttle:3,1')
        ->name('register.resend');

    // Password recovery by SMS code (REQ-IAM-003).
    Route::get('password/forgot', [PasswordResetController::class, 'showRequest'])->name('password.request');
    Route::post('password/forgot', [PasswordResetController::class, 'requestCode'])
        ->middleware('throttle:6,1')
        ->name('password.request.store');
    Route::get('password/forgot/verify', [PasswordResetController::class, 'showVerify'])->name('password.reset.verify');
    Route::post('password/forgot/verify', [PasswordResetController::class, 'reset'])
        ->middleware('throttle:10,1')
        ->name('password.reset.store');
});

Route::middleware(['web', 'auth'])->group(function (): void {
    Route::post('logout', [LoginController::class, 'destroy'])->name('logout');

    Route::get('password/change', [PasswordController::class, 'edit'])->name('password.change');
    Route::post('password/change', [PasswordController::class, 'update'])->name('password.update');

    // Profile editing; a mobile change needs its own code sent to the new number (REQ-IAM-004).
    Route::get('account/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::post('account/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::post('account/profile/mobile', [ProfileController::class, 'requestMobileChange'])
        ->middleware('throttle:5,1')
        ->name('profile.mobile.request');
    Route::post('account/profile/mobile/confirm', [ProfileController::class, 'confirmMobileChange'])
        ->middleware('throttle:10,1')
        ->name('profile.mobile.confirm');
});

Route::middleware(['web', 'auth', 'password.changed'])->group(function (): void {
    Route::get('panel', [PanelController::class, 'redirect'])->name('panel.redirect');
    Route::post('panel/role', [PanelController::class, 'switchRole'])->name('panel.role.switch');

    Route::get('account', [PanelController::class, 'show'])
        ->defaults('panel', 'account')
        ->middleware('active-role:consumer')
        ->name('panel.consumer');

    Route::get('store', [PanelController::class, 'show'])
        ->defaults('panel', 'store')
        ->middleware('active-role:store')
        ->name('panel.store');

    Route::get('supplier', [PanelController::class, 'show'])
        ->defaults('panel', 'supplier')
        ->middleware('active-role:supplier')
        ->name('panel.supplier');

    Route::get('marketer', [PanelController::class, 'show'])
        ->defaults('panel', 'marketer')
        ->middleware('active-role:marketer')
        ->name('panel.marketer');
});
