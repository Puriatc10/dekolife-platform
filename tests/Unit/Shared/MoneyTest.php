<?php

declare(strict_types=1);

use App\Shared\Money;

it('creates money from an integer amount of Toman', function (): void {
    expect(Money::fromToman(90_000_000)->toToman())->toBe(90_000_000);
});

it('creates money from a numeric string', function (): void {
    expect(Money::fromToman('2500')->toToman())->toBe(2500);
});

it('rejects a non integer amount', function (): void {
    Money::fromToman('12.5');
})->throws(InvalidArgumentException::class);

it('adds and subtracts exactly', function (): void {
    $a = Money::fromToman(1_000_000);
    $b = Money::fromToman(250_000);

    expect($a->add($b)->toToman())->toBe(1_250_000)
        ->and($a->subtract($b)->toToman())->toBe(750_000);
});

it('computes a percentage with half-up rounding', function (): void {
    // 0.75% of 1,000,000 = 7,500
    expect(Money::fromToman(1_000_000)->percentage(75)->toToman())->toBe(7_500)
        // 0.5% of 33 = 0.165 => 0
        ->and(Money::fromToman(33)->percentage(50)->toToman())->toBe(0)
        // 0.5% of 300 = 1.5 => 2 (half-up)
        ->and(Money::fromToman(300)->percentage(50)->toToman())->toBe(2)
        // 35% of 1,000,000 = 350,000
        ->and(Money::fromToman(1_000_000)->percentage(3_500)->toToman())->toBe(350_000);
});

it('refuses a negative percentage', function (): void {
    Money::fromToman(1_000)->percentage(-1);
})->throws(InvalidArgumentException::class);

it('refuses division by zero', function (): void {
    Money::fromToman(1_000)->divide(0);
})->throws(InvalidArgumentException::class);

it('compares amounts', function (): void {
    $small = Money::fromToman(100);
    $large = Money::fromToman(200);

    expect($small->greaterThan($large))->toBeFalse()
        ->and($large->greaterThan($small))->toBeTrue()
        ->and($small->equals(Money::fromToman(100)))->toBeTrue()
        ->and(Money::zero()->isZero())->toBeTrue()
        ->and(Money::fromToman(-5)->isNegative())->toBeTrue();
});

it('formats with thousands separators', function (): void {
    expect(Money::fromToman(90_000_000)->format())->toBe('90,000,000')
        ->and(Money::fromToman(-1500)->format())->toBe('-1,500');
});

it('serialises to a json number', function (): void {
    expect(json_encode(Money::fromToman(1250)))->toBe('1250');
});
