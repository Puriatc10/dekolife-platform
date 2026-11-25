<?php

declare(strict_types=1);

namespace App\Shared;

use InvalidArgumentException;
use JsonSerializable;
use Stringable;

/**
 * Immutable money value object.
 *
 * The platform stores and calculates every amount as an integer number of Toman
 * (contract prices are quoted in Toman). Floating point is never used: rounding is
 * done once, explicitly, in {@see self::percentage()} and {@see self::divide()}.
 */
final readonly class Money implements JsonSerializable, Stringable
{
    private function __construct(public int $amount) {}

    public static function fromToman(int|string $amount): self
    {
        if (is_string($amount)) {
            if (! preg_match('/^-?\d+$/', $amount)) {
                throw new InvalidArgumentException("Money must be an integer amount of Toman, got [{$amount}].");
            }

            $amount = (int) $amount;
        }

        return new self($amount);
    }

    public static function zero(): self
    {
        return new self(0);
    }

    public function add(self $other): self
    {
        return new self($this->amount + $other->amount);
    }

    public function subtract(self $other): self
    {
        return new self($this->amount - $other->amount);
    }

    public function multiply(int $factor): self
    {
        return new self($this->amount * $factor);
    }

    /**
     * Percentage of this amount, expressed in basis points (1% = 100 bp),
     * rounded half-up to the nearest Toman.
     */
    public function percentage(int $basisPoints): self
    {
        if ($basisPoints < 0) {
            throw new InvalidArgumentException('Basis points must not be negative.');
        }

        $product = $this->amount * $basisPoints;

        return new self(intdiv($product + 5000, 10000));
    }

    /**
     * Integer division; the remainder is discarded (never used for money owed to a user).
     */
    public function divide(int $divisor): self
    {
        if ($divisor === 0) {
            throw new InvalidArgumentException('Cannot divide money by zero.');
        }

        return new self(intdiv($this->amount, $divisor));
    }

    public function min(self $other): self
    {
        return $this->amount <= $other->amount ? $this : $other;
    }

    public function max(self $other): self
    {
        return $this->amount >= $other->amount ? $this : $other;
    }

    public function isZero(): bool
    {
        return $this->amount === 0;
    }

    public function isNegative(): bool
    {
        return $this->amount < 0;
    }

    public function isPositive(): bool
    {
        return $this->amount > 0;
    }

    public function equals(self $other): bool
    {
        return $this->amount === $other->amount;
    }

    public function greaterThan(self $other): bool
    {
        return $this->amount > $other->amount;
    }

    public function toToman(): int
    {
        return $this->amount;
    }

    /**
     * Human readable form with thousands separators, e.g. 90000000 => "90,000,000".
     */
    public function format(): string
    {
        $sign = $this->amount < 0 ? '-' : '';

        return $sign.number_format(abs($this->amount));
    }

    public function jsonSerialize(): int
    {
        return $this->amount;
    }

    public function __toString(): string
    {
        return $this->format();
    }
}
