<?php

declare(strict_types=1);

namespace App\Modules\Merchants\Domain\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A store (فروشگاه/همکار) or supplier (تأمین‌کننده) profile awaiting, holding or having lost
 * admin approval (پیوست ۳ ۳-۱/۳-۴, پیوست ۶ بند ۱۰).
 *
 * The national id is never a plain column: `national_id_hash` enforces uniqueness and
 * `national_id_encrypted` is the privileged copy.
 *
 * @property int $id
 * @property int $user_id
 * @property string $type
 * @property string $business_name
 * @property string|null $owner_name
 * @property string|null $postal_code
 * @property string|null $province
 * @property string|null $city
 * @property string|null $address
 * @property string|null $phone
 * @property string $status
 * @property string|null $review_note
 * @property int|null $reviewed_by_id
 * @property Carbon|null $reviewed_at
 * @property Carbon $created_at
 * @property-read User|null $user
 */
final class MerchantProfile extends Model
{
    protected $table = 'mer_merchant_profiles';

    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'user_id', 'type', 'business_name', 'owner_name',
        'national_id_hash', 'national_id_encrypted',
        'postal_code', 'province', 'city', 'address', 'phone',
        'status', 'review_note', 'reviewed_by_id', 'reviewed_at',
    ];

    protected $hidden = ['national_id_hash', 'national_id_encrypted'];

    protected function casts(): array
    {
        return ['reviewed_at' => 'datetime'];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }
}
