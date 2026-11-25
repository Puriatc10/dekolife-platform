<?php

declare(strict_types=1);

namespace App\Modules\Identity\Domain\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $code
 * @property string $group
 * @property string $label_fa
 */
final class Permission extends Model
{
    protected $table = 'iam_permissions';

    protected $fillable = ['code', 'group', 'label_fa'];
}
