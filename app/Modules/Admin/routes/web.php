<?php

declare(strict_types=1);

use App\Modules\Admin\Http\Controllers\DashboardController;
use App\Modules\Admin\Http\Controllers\TwoFactorController;
use App\Modules\Merchants\Http\Controllers\Admin\MerchantReviewController;
use Illuminate\Support\Facades\Route;

/*
 * Admin panel routes (پیوست ۱ بند ۷: a separate path with mandatory 2FA).
 *
 * The guard is `admin`, so an ordinary storefront session cannot reach these routes even
 * if the URL is guessed; the role check follows, then the second factor.
 */
Route::middleware(['web', 'auth'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function (): void {
        Route::get('2fa', [TwoFactorController::class, 'show'])->name('2fa.show');
        Route::post('2fa', [TwoFactorController::class, 'verify'])->name('2fa.verify');

        Route::middleware(['active-role:admin', 'password.changed', 'admin.2fa'])->group(function (): void {
            Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

            // Store/supplier profile review: the only place those roles are granted.
            Route::get('merchants', [MerchantReviewController::class, 'index'])->name('merchants.index');
            Route::post('merchants/{profile}/approve', [MerchantReviewController::class, 'approve'])->name('merchants.approve');
            Route::post('merchants/{profile}/reject', [MerchantReviewController::class, 'reject'])->name('merchants.reject');
        });
    });
