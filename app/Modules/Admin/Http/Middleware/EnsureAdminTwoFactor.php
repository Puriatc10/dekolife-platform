<?php

declare(strict_types=1);

namespace App\Modules\Admin\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Second factor gate for the admin panel (پیوست ۱ بند ۷: «احراز هویت دو مرحله‌ای برای نقش ادمین»).
 *
 * The session flag is set only after a valid TOTP code; a fresh login always clears it,
 * so a session hijack does not inherit a verified second factor.
 */
final class EnsureAdminTwoFactor
{
    public const SESSION_KEY = 'admin.2fa_verified';

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user('admin') ?? $request->user();

        if ($user === null) {
            abort(Response::HTTP_UNAUTHORIZED);
        }

        if ($request->session()->get(self::SESSION_KEY) === true) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'error' => [
                    'code' => 'TWO_FACTOR_REQUIRED',
                    'message' => 'برای دسترسی به پنل مدیریت، کد دومرحله‌ای لازم است.',
                ],
            ], Response::HTTP_FORBIDDEN);
        }

        return redirect()->route('admin.2fa.show');
    }
}
