<?php

declare(strict_types=1);

namespace App\Modules\Identity\Http\Controllers;

use App\Modules\Admin\Application\AuditLogger;
use App\Modules\Identity\Domain\RoleCode;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Entry point for the four non-admin panels.
 *
 * Each panel is a separate URL because the active role decides which data scope and which
 * price list apply (ADR-0007). `/panel` sends the user to whichever panel their active role
 * owns, which is what the header's role switcher links to.
 */
final class PanelController
{
    public function redirect(Request $request): RedirectResponse
    {
        $role = $request->user()?->activeRoleCode();

        return redirect($role?->panelPath() ?? '/');
    }

    public function show(Request $request, string $panel): View
    {
        $role = $request->user()?->activeRoleCode();

        return view('identity::panel', [
            'user' => $request->user(),
            'role' => $role ?? RoleCode::Consumer,
            'panel' => $panel,
        ]);
    }

    public function switchRole(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'role' => ['required', 'string', 'in:'.implode(',', RoleCode::codes())],
        ]);

        $user = $request->user();

        // The switcher is not an escalation path: a role the user does not hold is refused
        // with 403 rather than a 500 from the domain guard (NEG-IAM-02).
        if (! $user->hasRole($validated['role'])) {
            abort(403, 'این سطح برای حساب شما فعال نشده است.');
        }

        $target = $user->switchActiveRole($validated['role']);

        app(AuditLogger::class)
            ->log('identity.role.switched', ['to' => $target->code]);

        return redirect($target->asCode()?->panelPath() ?? '/');
    }
}
