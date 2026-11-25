<?php

namespace Database\Seeders;

use App\Models\User;
use App\Modules\Identity\Database\Seeders\RolePermissionSeeder;
use App\Modules\Identity\Domain\RoleCode;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     *
     * The demo accounts exist so a reviewer can walk the role switch and the admin 2FA
     * challenge without an SMS provider. They are created with `must_change_password`,
     * which is also the contract's first-login rule for invited users (پیوست ۶ بند ۸).
     */
    public function run(): void
    {
        $this->call(RolePermissionSeeder::class);

        $accounts = [
            ['مدیر سامانه', 'admin@dekolife.test', RoleCode::Admin],
            ['فروشگاه نمونه', 'store@dekolife.test', RoleCode::Store],
            ['تأمین‌کننده نمونه', 'supplier@dekolife.test', RoleCode::Supplier],
            ['بازاریاب نمونه', 'marketer@dekolife.test', RoleCode::Marketer],
            ['مصرف‌کننده نمونه', 'consumer@dekolife.test', RoleCode::Consumer],
        ];

        foreach ($accounts as [$name, $email, $role]) {
            /** @var User $user */
            $user = User::query()->firstOrCreate(
                ['email' => $email],
                [
                    'name' => $name,
                    'password' => 'password',
                    'must_change_password' => true,
                    'mobile_verified_at' => now(),
                ],
            );

            $user->assignRole($role);
        }
    }
}
