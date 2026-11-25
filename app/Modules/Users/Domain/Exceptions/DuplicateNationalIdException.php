<?php

declare(strict_types=1);

namespace App\Modules\Users\Domain\Exceptions;

use RuntimeException;

/**
 * A national id is already registered. The message carries only a masked form — the
 * duplicate check must not become an oracle that reveals someone else's id (پیوست ۵ بند ۳).
 */
final class DuplicateNationalIdException extends RuntimeException
{
    private function __construct(string $masked)
    {
        parent::__construct("این کد ملی قبلاً ثبت شده است ({$masked}).");
    }

    public static function forMasked(string $masked): self
    {
        return new self($masked);
    }
}
