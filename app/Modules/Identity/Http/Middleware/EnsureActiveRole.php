<?php

declare(strict_types=1);

namespace App\Modules\Identity\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Requires the session's *active* role to be one of the listed roles.
 *
 * Usage: `->middleware('active-role:store')` or `'active-role:store,marketer'`.
 * A user who holds the role but has a different one active is refused — that is the
 * point of the secondary-role switch, and it must not be bypassable by calling another
 * panel's URL directly (NEG-IAM-03).
 */
final class EnsureActiveRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if ($user === null) {
            return $this->deny($request, 'unauthenticated');
        }

        $active = method_exists($user, 'activeRoleCode') ? $user->activeRoleCode() : null;

        if ($active === null) {
            return $this->deny($request, 'no-active-role');
        }

        $allowed = array_map(static fn (string $role): string => $role, $roles);

        if (! in_array($active->value, $allowed, true)) {
            return $this->deny($request, 'wrong-active-role');
        }

        // The panel path is derived from the active role, so a user cannot sit in one
        // panel while holding another role's powers.
        $expectedPrefix = $active->panelPath();

        if (! str_starts_with('/'.ltrim($request->path(), '/'), $expectedPrefix)) {
            return $this->deny($request, 'panel-mismatch');
        }

        return $next($request);
    }

    private function deny(Request $request, string $reason): Response
    {
        if ($request->expectsJson()) {
            // 403 without leaking whether the resource exists (permissions matrix legend).
            return response()->json([
                'error' => [
                    'code' => 'FORBIDDEN',
                    'message' => 'دسترسی به این بخش برای سطح فعال شما مجاز نیست.',
                    'reason' => $reason,
                ],
            ], Response::HTTP_FORBIDDEN);
        }

        abort(Response::HTTP_FORBIDDEN, 'دسترسی به این بخش برای سطح فعال شما مجاز نیست.');
    }
}
