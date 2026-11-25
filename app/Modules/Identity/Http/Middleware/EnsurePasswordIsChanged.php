<?php

declare(strict_types=1);

namespace App\Modules\Identity\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Blocks every authenticated route until a first-login password change has happened.
 *
 * This is a gate, not a nag: an invited store that never changes its temporary password
 * must not be able to transact (پیوست ۶ بند ۸).
 */
final class EnsurePasswordIsChanged
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null || ! method_exists($user, 'needsPasswordChange') || ! $user->needsPasswordChange()) {
            return $next($request);
        }

        if ($request->routeIs('password.change', 'password.update', 'logout')) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'error' => [
                    'code' => 'PASSWORD_CHANGE_REQUIRED',
                    'message' => 'پیش از ادامه، گذرواژهٔ خود را تغییر دهید.',
                ],
            ], Response::HTTP_FORBIDDEN);
        }

        return redirect()->route('password.change');
    }
}
