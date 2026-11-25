<?php

declare(strict_types=1);

use App\Shared\Security\Totp;

/*
 * RFC 6238 Appendix B test vectors (SHA-1, 8 digits, 30 s step).
 * The secret is the ASCII string "12345678901234567890", base32-encoded.
 * A wrong implementation of the counter packing or the dynamic truncation passes a
 * "code changes every 30 seconds" test and fails these.
 */
$secret = 'GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ';
$totp = new Totp($secret, digits: 8);

it('generates the RFC 6238 vectors', function (int $timestamp, string $expected) use ($totp): void {
    expect($totp->code($timestamp))->toBe($expected);
})->with([
    [59, '94287082'],
    [1111111109, '07081804'],
    [1111111111, '14050471'],
    [1234567890, '89005924'],
    [2000000000, '69279037'],
]);

it('accepts a code inside the drift window and rejects one outside it', function () use ($totp): void {
    $code = $totp->code(1_000_000_000);

    expect($totp->verify($code, 1_000_000_000))->toBeTrue()
        ->and($totp->verify($code, 1_000_000_030))->toBeTrue()   // one step late
        ->and($totp->verify($code, 1_000_000_090))->toBeFalse(); // three steps late
});

it('rejects malformed input without throwing', function () use ($totp): void {
    expect($totp->verify('', 1_000_000_000))->toBeFalse()
        ->and($totp->verify('abc', 1_000_000_000))->toBeFalse()
        ->and($totp->verify('12345', 1_000_000_000))->toBeFalse();
});

it('generates a usable random secret', function (): void {
    $secret = Totp::generateSecret();
    $totp = new Totp($secret);

    expect($secret)->toMatch('/^[A-Z2-7]+$/')
        ->and($totp->verify($totp->code()))->toBeTrue();
});
