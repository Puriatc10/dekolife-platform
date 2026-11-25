<?php

declare(strict_types=1);

use App\Modules\Identity\Http\Controllers\Auth\LoginController;
use App\Modules\Identity\Http\Controllers\Auth\PasswordController;
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
});

Route::middleware(['web', 'auth'])->group(function (): void {
    Route::post('logout', [LoginController::class, 'destroy'])->name('logout');

    Route::get('password/change', [PasswordController::class, 'edit'])->name('password.change');
    Route::post('password/change', [PasswordController::class, 'update'])->name('password.update');
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
