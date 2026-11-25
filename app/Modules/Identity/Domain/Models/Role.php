<?php

declare(strict_types=1);

namespace App\Modules\Identity\Domain\Models;

use App\Modules\Identity\Domain\RoleCode;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property int $id
 * @property string $code
 * @property string $label_fa
 * @property string $panel_path
 */
final class Role extends Model
{
    protected $table = 'iam_roles';

    protected $fillable = ['code', 'label_fa', 'panel_path', 'is_system'];

    protected function casts(): array
    {
        return ['is_system' => 'boolean'];
    }

    /** @return BelongsToMany<Permission, $this> */
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'iam_permission_role', 'role_id', 'permission_id');
    }

    public function asCode(): ?RoleCode
    {
        return RoleCode::tryFrom($this->code);
    }
}
