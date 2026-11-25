<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\Identity\Domain\RoleCode;
use App\Modules\Identity\Seeders\RolePermissionSeeder;
use App\Modules\Merchants\Application\MerchantProfileService;
use App\Modules\Merchants\Domain\Models\MerchantProfile;
use App\Modules\Users\Domain\Exceptions\DuplicateNationalIdException;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(RolePermissionSeeder::class);
});

it('records a store application as pending and grants no role yet', function (): void {
    $applicant = User::factory()->create(['mobile' => '09121220001', 'must_change_password' => false]);
    $applicant->assignRole(RoleCode::Consumer);

    $this->actingAs($applicant)->post('/merchant/profiles', [
        'type' => 'store',
        'business_name' => 'فروشگاه نمونه',
        'owner_name' => 'مالک نمونه',
        'national_id' => '0499370899',
        'city' => 'تهران',
    ])->assertRedirect(route('merchant.profiles.index'));

    $profile = MerchantProfile::query()->firstOrFail();

    expect($profile->status)->toBe(MerchantProfile::STATUS_PENDING)
        ->and($profile->user_id)->toBe($applicant->id)
        ->and($applicant->fresh()->hasRole(RoleCode::Store))->toBeFalse();
});

it('grants the store role only when an admin approves', function (): void {
    $applicant = User::factory()->create(['mobile' => '09121220002']);
    $applicant->assignRole(RoleCode::Consumer);

    $admin = User::factory()->create();
    $admin->assignRole(RoleCode::Admin);

    /** @var MerchantProfileService $service */
    $service = app(MerchantProfileService::class);

    $profile = $service->submit($applicant, 'store', [
        'business_name' => 'فروشگاه دوم',
        'national_id' => '0499370899',
    ]);

    expect($applicant->fresh()->hasRole(RoleCode::Store))->toBeFalse();

    $this->actingAs($admin);
    $service->approve($profile, $admin, 'مدارک کامل بود');

    $approved = $profile->fresh();

    expect($approved->status)->toBe(MerchantProfile::STATUS_APPROVED)
        ->and($approved->reviewed_by_id)->toBe($admin->id)
        ->and($applicant->fresh()->hasRole(RoleCode::Store))->toBeTrue();

    $this->assertDatabaseHas('adm_audit_logs', ['event' => 'merchant.profile.approved']);

    // The newly granted role now opens the store panel — once it is the active role, which is
    // the rule the `active-role` middleware enforces for every panel.
    $this->actingAs($applicant->fresh())
        ->withSession(['active_role_code' => RoleCode::Store->value])
        ->get('/store')
        ->assertOk();
});

it('keeps the rejection reason and grants nothing', function (): void {
    $applicant = User::factory()->create(['mobile' => '09121220003']);
    $applicant->assignRole(RoleCode::Consumer);

    $admin = User::factory()->create();
    $admin->assignRole(RoleCode::Admin);

    $service = app(MerchantProfileService::class);

    $profile = $service->submit($applicant, 'supplier', [
        'business_name' => 'تأمین‌کننده نمونه',
        'national_id' => '0499370899',
    ]);

    $this->actingAs($admin);
    $service->reject($profile, $admin, 'کد پستی نامعتبر است');

    $rejected = $profile->fresh();

    expect($rejected->status)->toBe(MerchantProfile::STATUS_REJECTED)
        ->and($rejected->review_note)->toBe('کد پستی نامعتبر است')
        ->and($applicant->fresh()->hasRole(RoleCode::Supplier))->toBeFalse();
});

it('refuses a second profile that reuses a registered national id', function (): void {
    $first = User::factory()->create(['mobile' => '09121220004']);
    $first->assignRole(RoleCode::Consumer);

    $second = User::factory()->create(['mobile' => '09121220005']);
    $second->assignRole(RoleCode::Consumer);

    $service = app(MerchantProfileService::class);

    $service->submit($first, 'store', [
        'business_name' => 'فروشگاه اول',
        'national_id' => '0499370899',
    ]);

    expect(fn () => $service->submit($second, 'store', [
        'business_name' => 'فروشگاه دوم',
        'national_id' => '0499370899',
    ]))->toThrow(DuplicateNationalIdException::class);
});

it('is idempotent when the same applicant resubmits a rejected profile', function (): void {
    $applicant = User::factory()->create(['mobile' => '09121220006']);
    $applicant->assignRole(RoleCode::Consumer);

    $admin = User::factory()->create();
    $admin->assignRole(RoleCode::Admin);

    $service = app(MerchantProfileService::class);

    $profile = $service->submit($applicant, 'store', [
        'business_name' => 'فروشگاه سوم',
        'national_id' => '0499370899',
    ]);

    $this->actingAs($admin);
    $service->reject($profile, $admin, 'نشانی ناقص');

    $resubmitted = $service->submit($applicant->fresh(), 'store', [
        'business_name' => 'فروشگاه سوم',
        'national_id' => '0499370899',
        'address' => 'تهران، خیابان نمونه',
    ]);

    expect($resubmitted->id)->toBe($profile->id)
        ->and($resubmitted->status)->toBe(MerchantProfile::STATUS_PENDING)
        ->and(MerchantProfile::query()->count())->toBe(1);
});
