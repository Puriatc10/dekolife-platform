<?php

declare(strict_types=1);

namespace App\Modules\Identity\Domain;

/**
 * Permission codes. One code per capability row of docs/requirements/permissions-matrix.md,
 * so a policy can be traced back to the contract instead of to a developer's memory.
 */
enum PermissionCode: string
{
    // Identity and account
    case AccountLogin = 'account.login';
    case AccountProfileEdit = 'account.profile.edit';
    case AccountSecondaryRoleSwitch = 'account.role.switch';

    // Platform administration (admin only, always audited)
    case AdminAccess = 'admin.access';
    case AdminUsersManage = 'admin.users.manage';
    case AdminRolesManage = 'admin.roles.manage';
    case AdminAuditRead = 'admin.audit.read';

    // Scope-restricted capabilities (always applied with an ownership scope)
    case StoreCatalogManage = 'store.catalog.manage';
    case StoreOrdersRead = 'store.orders.read';
    case SupplierCatalogManage = 'supplier.catalog.manage';
    case MarketerNetworkRead = 'marketer.network.read';
    case ConsumerOrdersRead = 'consumer.orders.read';

    public function group(): string
    {
        return match (true) {
            str_starts_with($this->value, 'admin.') => 'administration',
            str_starts_with($this->value, 'store.') => 'store',
            str_starts_with($this->value, 'supplier.') => 'supplier',
            str_starts_with($this->value, 'marketer.') => 'marketer',
            str_starts_with($this->value, 'consumer.') => 'consumer',
            default => 'account',
        };
    }

    public function labelFa(): string
    {
        return match ($this) {
            self::AccountLogin => 'ورود به حساب',
            self::AccountProfileEdit => 'ویرایش پروفایل خود',
            self::AccountSecondaryRoleSwitch => 'تغییر سطح ثانویه',
            self::AdminAccess => 'دسترسی به پنل مدیریت',
            self::AdminUsersManage => 'مدیریت کاربران',
            self::AdminRolesManage => 'مدیریت نقش‌ها و سطوح',
            self::AdminAuditRead => 'مشاهده گزارش رویدادها',
            self::StoreCatalogManage => 'مدیریت کاتالوگ فروشگاه',
            self::StoreOrdersRead => 'مشاهده سفارش‌های فروشگاه',
            self::SupplierCatalogManage => 'مدیریت کاتالوگ تأمین‌کننده',
            self::MarketerNetworkRead => 'مشاهده شبکه بازاریاب',
            self::ConsumerOrdersRead => 'مشاهده سفارش‌های خود',
        };
    }
}
