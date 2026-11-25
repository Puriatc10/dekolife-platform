<?php

declare(strict_types=1);

namespace App\Modules\Users\Application;

use App\Models\User;
use App\Modules\Users\Domain\Exceptions\DuplicateNationalIdException;
use App\Modules\Users\Domain\NationalId;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

/**
 * The single place that decides whether a national id is already taken (پیوست ۵ بند ۳).
 *
 * Uniqueness is enforced by the database (unique index on the hash), not by a read-then-write
 * check: two registrations racing on the same id would both pass a SELECT and both succeed.
 */
final class NationalIdRegistry
{
    /** @throws DuplicateNationalIdException */
    public function claim(NationalId $nationalId, ?User $user = null, string $source = 'registration'): void
    {
        $hash = $nationalId->hash();

        $existing = DB::table('usr_national_ids')->where('hash', $hash)->first();

        if ($existing !== null) {
            if ($user !== null && (int) $existing->user_id === (int) $user->getKey()) {
                return; // the same person, already registered
            }

            throw DuplicateNationalIdException::forMasked($nationalId->masked());
        }

        try {
            DB::table('usr_national_ids')->insert([
                'hash' => $hash,
                'encrypted' => $nationalId->encrypted(),
                'user_id' => $user?->getKey(),
                'source' => $source,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (UniqueConstraintViolationException) {
            // Lost the race with a concurrent registration: same answer, no 500.
            throw DuplicateNationalIdException::forMasked($nationalId->masked());
        }
    }

    public function isTaken(NationalId $nationalId): bool
    {
        return DB::table('usr_national_ids')->where('hash', $nationalId->hash())->exists();
    }

    /** The id belonging to a user, for a privileged screen; null when none is on file. */
    public function decryptFor(User $user): ?string
    {
        $row = DB::table('usr_national_ids')->where('user_id', $user->getKey())->first();

        if ($row === null) {
            return null;
        }

        return Crypt::decryptString((string) $row->encrypted);
    }
}
