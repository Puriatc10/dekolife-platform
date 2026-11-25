<?php

declare(strict_types=1);

namespace App\Modules\Users\Domain;

use Illuminate\Support\Facades\Crypt;
use InvalidArgumentException;

/**
 * Iranian national id (کد ملی): normalisation, checksum, and the two representations we
 * store — a keyed hash for the uniqueness constraint and an encrypted copy for display to
 * privileged roles (AMB-CTR-003).
 */
final readonly class NationalId
{
    private function __construct(public string $value) {}

    public static function fromString(string $raw): self
    {
        $persian = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
        $arabic = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'];
        $latin = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];

        $digits = str_replace(
            array_merge($persian, $arabic),
            array_merge($latin, $latin),
            $raw,
        );

        $digits = preg_replace('/\D/', '', $digits) ?? '';

        if (strlen($digits) !== 10) {
            throw new InvalidArgumentException('کد ملی باید ۱۰ رقم باشد.');
        }

        return new self($digits);
    }

    public function isValid(): bool
    {
        if (preg_match('/^(\d)\1{9}$/', $this->value) === 1) {
            return false; // 1111111111 and friends are never valid
        }

        $sum = 0;

        for ($i = 0; $i < 9; $i++) {
            $sum += ((int) $this->value[$i]) * (10 - $i);
        }

        $remainder = $sum % 11;
        $check = (int) $this->value[9];

        return ($remainder < 2 && $check === $remainder)
            || ($remainder >= 2 && $check === 11 - $remainder);
    }

    /** Deterministic, keyed: the same id always hashes the same way, and a dump is useless without the key. */
    public function hash(): string
    {
        return hash_hmac('sha256', $this->value, (string) config('app.key'));
    }

    public function encrypted(): string
    {
        return Crypt::encryptString($this->value);
    }

    /** 123*****89 — what a support screen may show without exposing the full id. */
    public function masked(): string
    {
        return substr($this->value, 0, 3).'*****'.substr($this->value, -2);
    }
}
