<?php

declare(strict_types=1);

namespace App\Modules\Identity\Domain\Concerns;

use App\Modules\Identity\Domain\Models\Role;
use App\Modules\Identity\Domain\PermissionCode;
use App\Modules\Identity\Domain\RoleCode;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use RuntimeException;

/**
 * Role grants for a user.
 *
 * A user holds a *set* of roles (IAM-005, پیوست ۷ بند ۶) and exactly one of them is
 * active per session. The active role — not the role set — decides which panel and which
 * permissions apply, so a store owner who is also a marketer cannot see both price lists
 * at once by accident (ADR-0007).
 *
 * @property-read Collection<int, Role> $roles
 */
trait HasRoles
{
    public const ACTIVE_ROLE_SESSION_KEY = 'active_role_code';

    /** @return BelongsToMany<Role, $this> */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'iam_user_roles', 'user_id', 'role_id')
            ->withPivot(['granted_by_id', 'granted_at', 'revoked_at'])
            ->withTimestamps()
            ->wherePivotNull('revoked_at');
    }

    public function assignRole(RoleCode|string $role, ?self $grantedBy = null): void
    {
        $code = $role instanceof RoleCode ? $role->value : $role;

        $model = Role::query()->where('code', $code)->firstOrFail();

        $this->roles()->syncWithoutDetaching([
            $model->id => [
                'granted_by_id' => $grantedBy?->getKey(),
                'granted_at' => now(),
                'revoked_at' => null,
            ],
        ]);

        $this->unsetRelation('roles');
    }

    public function revokeRole(RoleCode|string $role): void
    {
        $code = $role instanceof RoleCode ? $role->value : $role;

        $model = Role::query()->where('code', $code)->firstOrFail();

        $this->roles()->updateExistingPivot($model->id, ['revoked_at' => now()]);
        $this->unsetRelation('roles');
    }

    public function hasRole(RoleCode|string $role): bool
    {
        $code = $role instanceof RoleCode ? $role->value : $role;

        return $this->roles->contains(static fn (Role $held): bool => $held->code === $code);
    }

    /** @return array<int, string> */
    public function roleCodes(): array
    {
        return $this->roles->pluck('code')->all();
    }

    public function isAdmin(): bool
    {
        return $this->hasRole(RoleCode::Admin);
    }

    /**
     * The role currently driving the session. Falls back to the user's first live role
     * when nothing (or a stale, revoked code) is stored in the session.
     */
    public function activeRole(): ?Role
    {
        $stored = session(self::ACTIVE_ROLE_SESSION_KEY);

        $role = $stored === null
            ? null
            : $this->roles->firstWhere('code', $stored);

        if ($role !== null) {
            return $role;
        }

        $fallback = $this->roles->first();

        if ($fallback !== null) {
            session([self::ACTIVE_ROLE_SESSION_KEY => $fallback->code]);
        }

        return $fallback;
    }

    public function activeRoleCode(): ?RoleCode
    {
        return $this->activeRole()?->asCode();
    }

    /**
     * Switch the active role. Refuses a role the user does not hold — the check is here,
     * not in the controller, because every caller must be bound by it (NEG-IAM-02).
     */
    public function switchActiveRole(RoleCode|string $role): Role
    {
        $code = $role instanceof RoleCode ? $role->value : $role;

        $target = $this->roles->firstWhere('code', $code);

        if ($target === null) {
            throw new RuntimeException("User {$this->getKey()} does not hold the role [{$code}].");
        }

        session([self::ACTIVE_ROLE_SESSION_KEY => $target->code]);

        return $target;
    }

    /**
     * Permissions of the *active* role only. Deliberately not a union across roles:
     * unioning is how a consumer+marketer account ends up seeing wholesale prices.
     */
    public function hasPermission(PermissionCode|string $permission): bool
    {
        $code = $permission instanceof PermissionCode ? $permission->value : $permission;

        $active = $this->activeRole();

        if ($active === null) {
            return false;
        }

        return $active->permissions()->where('code', $code)->exists();
    }
}
