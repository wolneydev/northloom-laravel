<?php

declare(strict_types=1);

namespace App\Domain\Financials\ValueObjects;

use InvalidArgumentException;

final readonly class Money
{
    private const MAX_MINOR_UNITS = 999999999999999;

    private function __construct(private int $minorUnits) {}

    public static function fromString(string $amount): self
    {
        if (preg_match('/^\d+(?:\.\d{1,2})?$/', $amount) !== 1) {
            throw new InvalidArgumentException('Money must be a non-negative decimal string with at most two decimal places.');
        }

        [$whole, $fraction] = array_pad(explode('.', $amount, 2), 2, '');
        $whole = ltrim($whole, '0') ?: '0';
        $fraction = str_pad($fraction, 2, '0');

        if (strlen($whole) > 13) {
            throw new InvalidArgumentException('Money exceeds the supported range.');
        }

        $minorUnits = ((int) $whole * 100) + (int) $fraction;

        if ($minorUnits > self::MAX_MINOR_UNITS) {
            throw new InvalidArgumentException('Money exceeds the supported range.');
        }

        return new self($minorUnits);
    }

    public function minorUnits(): int
    {
        return $this->minorUnits;
    }

    public function toString(): string
    {
        return intdiv($this->minorUnits, 100).'.'.str_pad((string) ($this->minorUnits % 100), 2, '0', STR_PAD_LEFT);
    }

    public function isPositive(): bool
    {
        return $this->minorUnits > 0;
    }

    public function isGreaterThanOrEqualTo(self $other): bool
    {
        return $this->minorUnits >= $other->minorUnits;
    }

    public function subtract(self $other): self
    {
        if (! $this->isGreaterThanOrEqualTo($other)) {
            throw new InvalidArgumentException('The subtraction would produce a negative amount.');
        }

        return new self($this->minorUnits - $other->minorUnits);
    }
}
