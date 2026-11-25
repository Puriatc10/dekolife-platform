<?php

namespace Database\Seeders;

use App\Models\User;
use App\Modules\Identity\Domain\RoleCode;
use App\Modules\Identity\Seeders\RolePermissionSeeder;
use App\Modules\Merchants\Application\MerchantProfileService;
use App\Modules\Merchants\Domain\Models\MerchantProfile;
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
            ['مدیر سامانه', 'admin@dekolife.test', RoleCode::Admin, '09120000001'],
            ['فروشگاه نمونه', 'store@dekolife.test', RoleCode::Store, '09120000002'],
            ['تأمین‌کننده نمونه', 'supplier@dekolife.test', RoleCode::Supplier, '09120000003'],
            ['بازاریاب نمونه', 'marketer@dekolife.test', RoleCode::Marketer, '09120000004'],
            ['مصرف‌کننده نمونه', 'consumer@dekolife.test', RoleCode::Consumer, '09120000005'],
        ];

        $users = [];

        foreach ($accounts as [$name, $email, $role, $mobile]) {
            /** @var User $user */
            $user = User::query()->firstOrCreate(
                ['email' => $email],
                [
                    'name' => $name,
                    'mobile' => $mobile,
                    'password' => 'password',
                    'must_change_password' => true,
                    'mobile_verified_at' => now(),
                ],
            );

            $user->assignRole($role);

            $users[$role->value] = $user;
        }

        // One pending application so the admin review screen has real content to show in the
        // preview gallery and in a fresh local install (پیوست ۶ بند ۱۰).
        $applicant = $users[RoleCode::Consumer->value];
        $alreadySeeded = MerchantProfile::query()
            ->where('user_id', $applicant->getKey())
            ->where('type', 'store')
            ->exists();

        if (! $alreadySeeded) {
            app(MerchantProfileService::class)->submit($applicant, 'store', [
                'business_name' => 'فروشگاه متقاضی نمونه',
                'owner_name' => 'متقاضی نمونه',
                'national_id' => '0499370899',
                'province' => 'تهران',
                'city' => 'تهران',
                'postal_code' => '1234567890',
                'address' => 'تهران، خیابان نمونه، پلاک ۱',
                'phone' => '02100000000',
            ]);
        }
    }
}
