<?php

declare(strict_types=1);

use App\Modules\Identity\Http\Controllers\Api\AuthTokenController;
use App\Modules\Identity\Http\Controllers\Api\MeController;
use Illuminate\Support\Facades\Route;

/*
 * Mobile/API surface (routes/api.php -> /api/v1).
 *
 * The token is the only credential: no session, no CSRF. `auth:sanctum` on everything except
 * the two token endpoints, and the token-issuing routes are rate-limited because they are the
 * one place an unauthenticated caller can spend SMS credit.
 */
Route::prefix('api/v1')->name('api.v1.')->group(function (): void {
    Route::middleware(['throttle:6,1'])->group(function (): void {
        Route::post('auth/otp', [AuthTokenController::class, 'requestOtp'])->name('auth.otp');
        Route::post('auth/otp-token', [AuthTokenController::class, 'storeFromOtp'])->name('auth.otp-token');
        Route::post('auth/token', [AuthTokenController::class, 'store'])->name('auth.token');
    });

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::get('me', MeController::class)->name('me');
        Route::post('auth/logout', [AuthTokenController::class, 'destroy'])->name('auth.logout');
    });
});
