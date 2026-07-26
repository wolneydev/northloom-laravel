<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Financials;

use App\Domain\Financials\ValueObjects\Money;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class MoneyTest extends TestCase
{
    #[DataProvider('validAmounts')]
    public function test_it_parses_and_canonicalizes_exact_decimal_strings(string $input, string $expected, int $minorUnits): void
    {
        $money = Money::fromString($input);

        self::assertSame($expected, $money->toString());
        self::assertSame($minorUnits, $money->minorUnits());
    }

    public static function validAmounts(): array
    {
        return [
            ['0', '0.00', 0],
            ['1.2', '1.20', 120],
            ['001.20', '1.20', 120],
            ['9999999999999.99', '9999999999999.99', 999999999999999],
        ];
    }

    #[DataProvider('invalidAmounts')]
    public function test_it_rejects_invalid_or_overprecise_values(string $amount): void
    {
        $this->expectException(InvalidArgumentException::class);

        Money::fromString($amount);
    }

    public static function invalidAmounts(): array
    {
        return [[''], ['-1.00'], ['1.001'], ['1e2'], ['1,00'], ['10000000000000.00']];
    }

    public function test_it_compares_and_subtracts_using_minor_units(): void
    {
        $balance = Money::fromString('10.00');
        $amount = Money::fromString('2.35');

        self::assertTrue($balance->isGreaterThanOrEqualTo($amount));
        self::assertSame('7.65', $balance->subtract($amount)->toString());
    }
}
