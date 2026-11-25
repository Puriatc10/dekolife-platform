<?php

declare(strict_types=1);

namespace App\Modules\Merchants\Application;

use App\Models\User;
use App\Modules\Admin\Application\AuditLogger;
use App\Modules\Identity\Domain\RoleCode;
use App\Modules\Merchants\Domain\Models\MerchantProfile;
use App\Modules\Users\Application\NationalIdRegistry;
use App\Modules\Users\Domain\Exceptions\DuplicateNationalIdException;
use App\Modules\Users\Domain\NationalId;
use DomainException;
use Illuminate\Support\Facades\DB;

/**
 * Store/supplier onboarding and review.
 *
 * A profile is created `pending`; only an admin decision grants the role, which is why the
 * role is never assigned by the applicant's own request (NEG-MER-01). Rejection keeps the
 * row so the applicant can see why and resubmit.
 */
final class MerchantProfileService
{
    public function __construct(
        private readonly NationalIdRegistry $nationalIds,
        private readonly AuditLogger $audit,
    ) {}

    /**
     * @param  array{business_name: string, owner_name?: string, national_id: string, postal_code?: string, province?: string, city?: string, address?: string, phone?: string}  $data
     *
     * @throws DuplicateNationalIdException
     * @throws DomainException when the type is unknown or an approved profile already exists
     */
    public function submit(User $user, string $type, array $data): MerchantProfile
    {
        if (! in_array($type, ['store', 'supplier'], true)) {
            throw new DomainException('نوع پروفایل نامعتبر است.');
        }

        $nationalId = NationalId::fromString($data['national_id']);

        if (! $nationalId->isValid()) {
            throw new DomainException('کد ملی وارد‌شده معتبر نیست.');
        }

        // A rejected profile can be resubmitted; an already approved one cannot be duplicated.
        $existing = MerchantProfile::query()
            ->where('user_id', $user->getKey())
            ->where('type', $type)
            ->first();

        if ($existing !== null && $existing->isApproved()) {
            throw new DomainException('پروفایل تأییدشدهٔ این نوع برای شما وجود دارد.');
        }

        return DB::transaction(function () use ($existing, $user, $type, $data, $nationalId): MerchantProfile {
            $this->nationalIds->claim($nationalId, $user, 'merchant_profile');

            $attributes = [
                'type' => $type,
                'business_name' => $data['business_name'],
                'owner_name' => $data['owner_name'] ?? $user->name,
                'national_id_hash' => $nationalId->hash(),
                'national_id_encrypted' => $nationalId->encrypted(),
                'postal_code' => $data['postal_code'] ?? null,
                'province' => $data['province'] ?? null,
                'city' => $data['city'] ?? null,
                'address' => $data['address'] ?? null,
                'phone' => $data['phone'] ?? null,
                'status' => MerchantProfile::STATUS_PENDING,
                'review_note' => null,
                'reviewed_by_id' => null,
                'reviewed_at' => null,
            ];

            if ($existing !== null) {
                $existing->update($attributes);
                $profile = $existing;
            } else {
                $profile = MerchantProfile::query()->create($attributes + ['user_id' => $user->getKey()]);
            }

            $this->audit->log('merchant.profile.submitted', ['type' => $type], $profile);

            return $profile;
        });
    }

    /** Approval grants the role; the audit row records who decided and what changed. */
    public function approve(MerchantProfile $profile, User $admin, ?string $note = null): MerchantProfile
    {
        if (! $profile->isPending()) {
            throw new DomainException('این پروفایل در وضعیت بررسی نیست.');
        }

        return DB::transaction(function () use ($profile, $admin, $note): MerchantProfile {
            $profile->update([
                'status' => MerchantProfile::STATUS_APPROVED,
                'review_note' => $note,
                'reviewed_by_id' => $admin->getKey(),
                'reviewed_at' => now(),
            ]);

            $role = $profile->type === 'supplier' ? RoleCode::Supplier : RoleCode::Store;

            $profile->user->assignRole($role, $admin);

            $this->audit->log('merchant.profile.approved', ['type' => $profile->type, 'role' => $role->value], $profile);

            return $profile->refresh();
        });
    }

    public function reject(MerchantProfile $profile, User $admin, string $reason): MerchantProfile
    {
        if (! $profile->isPending()) {
            throw new DomainException('این پروفایل در وضعیت بررسی نیست.');
        }

        return DB::transaction(function () use ($profile, $admin, $reason): MerchantProfile {
            $profile->update([
                'status' => MerchantProfile::STATUS_REJECTED,
                'review_note' => $reason,
                'reviewed_by_id' => $admin->getKey(),
                'reviewed_at' => now(),
            ]);

            $this->audit->log('merchant.profile.rejected', ['type' => $profile->type, 'reason' => $reason], $profile);

            return $profile->refresh();
        });
    }
}
