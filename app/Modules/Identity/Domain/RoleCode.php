<?php

declare(strict_types=1);

namespace App\Modules\Identity\Domain;

/**
 * The five independent user levels of the platform (appendix 3), plus the panel each
 * one lands on. The codes are stable: they appear in the database, in policies, in
 * route middleware and in every API payload, so they are never renamed casually.
 */
enum RoleCode: string
{
    case Consumer = 'consumer';
    case Store = 'store';
    case Supplier = 'supplier';
    case Marketer = 'marketer';
    case Admin = 'admin';

    public function labelFa(): string
    {
        return match ($this) {
            self::Consumer => 'مصرف‌کننده',
            self::Store => 'فروشگاه (همکار)',
            self::Supplier => 'تأمین‌کننده',
            self::Marketer => 'بازاریاب',
            self::Admin => 'مدیر سامانه',
        };
    }

    public function panelPath(): string
    {
        return match ($this) {
            self::Consumer => '/account',
            self::Store => '/store',
            self::Supplier => '/supplier',
            self::Marketer => '/marketer',
            self::Admin => '/admin',
        };
    }

    /** The guard the panel authenticates through. Admin panels are isolated (پیوست ۱ بند ۷). */
    public function guard(): string
    {
        return $this === self::Admin ? 'admin' : 'web';
    }

    /** @return array<int, string> */
    public static function codes(): array
    {
        return array_map(static fn (self $role): string => $role->value, self::cases());
    }
}
