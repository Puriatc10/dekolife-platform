<?php

declare(strict_types=1);

namespace App\Shared\Security;

use InvalidArgumentException;

/**
 * RFC 6238 TOTP (SHA-1, 6 digits, 30 s step) — the second factor for the admin panel
 * (پیوست ۱ بند ۷).
 *
 * Implemented here rather than pulled in as a package: it is 40 lines of HMAC, it is
 * verifiable against the RFC's own vectors (see TotpTest), and a dependency that only
 * wraps `hash_hmac` cannot be audited more cheaply than the call it wraps.
 */
final readonly class Totp
{
    private const BASE32_ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    public function __construct(
        private string $secret,
        private int $digits = 6,
        private int $period = 30,
    ) {
        if ($secret === '') {
            throw new InvalidArgumentException('The TOTP secret must not be empty.');
        }
    }

    /** Code for a given unix timestamp (defaults to now). */
    public function code(?int $timestamp = null): string
    {
        $counter = intdiv($timestamp ?? time(), $this->period);

        $binary = pack('N*', 0).pack('N*', $counter);
        $hash = hash_hmac('sha1', $binary, $this->base32Decode($this->secret), true);

        $offset = ord($hash[19]) & 0x0F;
        $value = (
            ((ord($hash[$offset]) & 0x7F) << 24) |
            ((ord($hash[$offset + 1]) & 0xFF) << 16) |
            ((ord($hash[$offset + 2]) & 0xFF) << 8) |
            (ord($hash[$offset + 3]) & 0xFF)
        ) % (10 ** $this->digits);

        return str_pad((string) $value, $this->digits, '0', STR_PAD_LEFT);
    }

    /**
     * Verify a submitted code, allowing `$window` steps of clock drift on each side
     * (one step = 30 s). Uses hash_equals to keep the comparison constant-time.
     */
    public function verify(string $code, ?int $timestamp = null, int $window = 1): bool
    {
        $code = preg_replace('/\D/', '', $code) ?? '';

        if (strlen($code) !== $this->digits) {
            return false;
        }

        $now = $timestamp ?? time();

        for ($drift = -$window; $drift <= $window; $drift++) {
            $candidate = $this->code($now + ($drift * $this->period));

            if (hash_equals($candidate, $code)) {
                return true;
            }
        }

        return false;
    }

    /** A new random base32 secret suitable for an authenticator app. */
    public static function generateSecret(int $bytes = 20): string
    {
        return self::base32Encode(random_bytes($bytes));
    }

    /** otpauth:// URI for the enrolment QR code. */
    public function provisioningUri(string $account, string $issuer): string
    {
        return sprintf(
            'otpauth://totp/%s:%s?secret=%s&issuer=%s&digits=%d&period=%d',
            rawurlencode($issuer),
            rawurlencode($account),
            $this->secret,
            rawurlencode($issuer),
            $this->digits,
            $this->period,
        );
    }

    public function secret(): string
    {
        return $this->secret;
    }

    private function base32Decode(string $encoded): string
    {
        $encoded = strtoupper(preg_replace('/[^A-Z2-7]/i', '', $encoded) ?? '');
        $bits = '';

        foreach (str_split($encoded) as $char) {
            $bits .= str_pad(decbin((int) strpos(self::BASE32_ALPHABET, $char)), 5, '0', STR_PAD_LEFT);
        }

        $binary = '';

        foreach (str_split($bits, 8) as $byte) {
            if (strlen($byte) === 8) {
                $binary .= chr((int) bindec($byte));
            }
        }

        return $binary;
    }

    private static function base32Encode(string $binary): string
    {
        $bits = '';

        foreach (str_split($binary) as $byte) {
            $bits .= str_pad(decbin(ord($byte)), 8, '0', STR_PAD_LEFT);
        }

        $encoded = '';

        foreach (str_split($bits, 5) as $chunk) {
            if (strlen($chunk) < 5) {
                $chunk = str_pad($chunk, 5, '0', STR_PAD_RIGHT);
            }

            $encoded .= self::BASE32_ALPHABET[(int) bindec($chunk)];
        }

        return $encoded;
    }
}
