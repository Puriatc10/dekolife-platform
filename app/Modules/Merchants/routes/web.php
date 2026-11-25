<?php

declare(strict_types=1);

use App\Modules\Merchants\Http\Controllers\MerchantProfileController;
use Illuminate\Support\Facades\Route;

/*
 * Store/supplier onboarding (پیوست ۳ ۳-۱/۳-۴).
 *
 * Any signed-in user may submit a profile — a customer who owns a shop applies from their own
 * account — but the role that opens the store panel is only granted by an admin approval, so
 * these routes deliberately do *not* require the store role they lead to.
 */
Route::middleware(['web', 'auth', 'password.changed'])
    ->name('merchant.')
    ->group(function (): void {
        Route::get('merchant/profiles', [MerchantProfileController::class, 'index'])->name('profiles.index');
        Route::get('merchant/profiles/new', [MerchantProfileController::class, 'create'])->name('profiles.create');
        Route::post('merchant/profiles', [MerchantProfileController::class, 'store'])->name('profiles.store');
    });
