<?php

declare(strict_types=1);

namespace App\Modules\Identity\Database\Seeders;

use App\Modules\Identity\Domain\Models\Permission;
use App\Modules\Identity\Domain\Models\Role;
use App\Modules\Identity\Domain\PermissionCode;
use App\Modules\Identity\Domain\RoleCode;
use Illuminate\Database\Seeder;

/**
 * Roles and the permission each role owns.
 *
 * Deliberately small: one code per capability row of the permissions matrix, and no role
 * re-uses another role's scoped permission (that union is how a consumer account ends up
 * seeing wholesale prices — see HasRoles::hasPermission()).
 */
final class RolePermissionSeeder extends Seeder
{
    /** @var array<string, array<int, PermissionCode>> */
    private const MATRIX = [
        RoleCode::Consumer->value => [
            PermissionCode::AccountLogin,
            PermissionCode::AccountProfileEdit,
            PermissionCode::ConsumerOrdersRead,
        ],
        RoleCode::Store->value => [
            PermissionCode::AccountLogin,
            PermissionCode::AccountProfileEdit,
            PermissionCode::StoreCatalogManage,
            PermissionCode::StoreOrdersRead,
        ],
        RoleCode::Supplier->value => [
            PermissionCode::AccountLogin,
            PermissionCode::AccountProfileEdit,
            PermissionCode::SupplierCatalogManage,
        ],
        RoleCode::Marketer->value => [
            PermissionCode::AccountLogin,
            PermissionCode::AccountProfileEdit,
            PermissionCode::MarketerNetworkRead,
        ],
        RoleCode::Admin->value => [
            PermissionCode::AccountLogin,
            PermissionCode::AccountProfileEdit,
            PermissionCode::AdminAccess,
            PermissionCode::AdminUsersManage,
            PermissionCode::AdminRolesManage,
            PermissionCode::AdminAuditRead,
        ],
    ];

    public function run(): void
    {
        $permissions = [];

        foreach (PermissionCode::cases() as $code) {
            $permissions[$code->value] = Permission::query()->firstOrCreate(
                ['code' => $code->value],
                ['group' => $code->group(), 'label_fa' => $code->labelFa()],
            );
        }

        foreach (RoleCode::cases() as $roleCode) {
            $role = Role::query()->firstOrCreate(
                ['code' => $roleCode->value],
                ['label_fa' => $roleCode->labelFa(), 'panel_path' => $roleCode->panelPath(), 'is_system' => true],
            );

            $grants = array_map(
                static fn (PermissionCode $permission): int => $permissions[$permission->value]->id,
                self::MATRIX[$roleCode->value],
            );

            $role->permissions()->sync($grants);
        }
    }
}
