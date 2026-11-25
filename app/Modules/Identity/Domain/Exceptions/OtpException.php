<?php

declare(strict_types=1);

namespace App\Modules\Identity\Domain\Exceptions;

use RuntimeException;

/**
 * A rejected one-time code. The reason is a stable code so a controller can map it to a
 * Persian message without string-matching exception text.
 */
final class OtpException extends RuntimeException
{
    private function __construct(public readonly string $reason)
    {
        parent::__construct($reason);
    }

    public static function notIssued(): self
    {
        return new self('not_issued');
    }

    public static function expired(): self
    {
        return new self('expired');
    }

    public static function invalid(): self
    {
        return new self('invalid');
    }

    public static function tooManyAttempts(): self
    {
        return new self('too_many_attempts');
    }

    public static function tooSoon(): self
    {
        return new self('too_soon');
    }

    public static function tooManyRequests(): self
    {
        return new self('too_many_requests');
    }

    public function messageFa(): string
    {
        return match ($this->reason) {
            'not_issued' => 'کدی برای این شماره صادر نشده است. دوباره درخواست دهید.',
            'expired' => 'کد منقضی شده است. کد تازه درخواست دهید.',
            'invalid' => 'کد وارد‌شده نادرست است.',
            'too_many_attempts' => 'تعداد تلاش‌های نادرست زیاد شد. کد تازه درخواست دهید.',
            'too_soon' => 'کد قبلی تازه ارسال شده است؛ یک دقیقه بعد دوباره تلاش کنید.',
            default => 'درخواست‌های ارسال کد زیاد بوده است؛ کمی بعد تلاش کنید.',
        };
    }
}
