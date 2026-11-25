<?php

declare(strict_types=1);

namespace App\Modules\Identity\Http\Controllers\Api;

use App\Models\User;
use App\Modules\Identity\Domain\RoleCode;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * GET /api/v1/me — who the token belongs to, which roles it holds and which panel each role
 * opens. The mobile client gets its navigation from this payload instead of hard-coding four
 * panel names.
 */
final class MeController
{
    public function __invoke(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        return response()->json([
            'data' => [
                'id' => $user->getKey(),
                'name' => $user->name,
                'email' => $user->email,
                'mobile' => $user->mobile,
                'mobile_verified' => $user->mobile_verified_at !== null,
                'must_change_password' => $user->needsPasswordChange(),
                'roles' => array_map(static fn ($role): array => [
                    'code' => $role->value,
                    'label' => $role->labelFa(),
                    'panel' => $role->panelPath(),
                ], $user->roles->map(static fn ($r) => RoleCode::from($r->code))->all()),
                'active_role' => $request->hasSession() ? $request->session()->get('active_role_code') : null,
            ],
        ]);
    }
}
