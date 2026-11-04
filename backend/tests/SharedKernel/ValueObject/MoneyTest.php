<?php

declare(strict_types=1);

namespace Tests\SharedKernel\ValueObject;

use App\SharedKernel\Enum\Currency;
use App\SharedKernel\ValueObject\Money;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[CoversClass(Money::class)]
#[Group('unit')]
final class MoneyTest extends TestCase
{
    /** Проверяет, что ofMinor корректно сохраняет переданные минимальные единицы и валюту. */
    public function testOfMinorCreatesInstanceWithGivenValues(): void
    {
        $money = Money::ofMinor(1999, Currency::USD);

        $this->assertSame(1999, $money->minorUnits);
        $this->assertSame(Currency::USD, $money->currency);
    }

    /** Проверяет создание суммы в 0 минимальных единиц — это валидное значение. */
    public function testOfMinorWithZero(): void
    {
        $money = Money::ofMinor(0, Currency::RUB);

        $this->assertSame(0, $money->minorUnits);
        $this->assertTrue($money->isZero());
    }

    /** Проверяет, что отрицательное количество минимальных единиц допустимо (возврат, перерасход). */
    public function testOfMinorWithNegativeValue(): void
    {
        $money = Money::ofMinor(-500, Currency::EUR);

        $this->assertSame(-500, $money->minorUnits);
        $this->assertTrue($money->isNegative());
    }

    /** Проверяет корректную работу с большими числами. */
    public function testOfMinorWithLargeValue(): void
    {
        $money = Money::ofMinor(99_999_999, Currency::USD);

        $this->assertSame(99_999_999, $money->minorUnits);
    }

    /** Проверяет конвертацию float-суммы (19.99) в минимальные единицы (1999) при передаче enum-валюты. */
    public function testOfMajorWithFloatAndEnum(): void
    {
        $money = Money::ofMajor(19.99, Currency::USD);

        $this->assertSame(1999, $money->minorUnits);
        $this->assertSame(Currency::USD, $money->currency);
    }

    /** Проверяет, что строковый код валюты ('RUB') автоматически распознаётся в enum. */
    public function testOfMajorWithFloatAndStringCurrency(): void
    {
        $money = Money::ofMajor(19.99, 'RUB');

        $this->assertSame(1999, $money->minorUnits);
        $this->assertSame(Currency::RUB, $money->currency);
    }

    /** Проверяет передачу целого числа как мажорной суммы (100 → 10000 копеек). */
    public function testOfMajorWithIntAmount(): void
    {
        $money = Money::ofMajor(100, Currency::EUR);

        $this->assertSame(10000, $money->minorUnits);
    }

    /** Проверяет создание нулевой суммы через ofMajor. */
    public function testOfMajorWithZero(): void
    {
        $money = Money::ofMajor(0.0, 'USD');

        $this->assertTrue($money->isZero());
        $this->assertSame(0, $money->minorUnits);
    }

    /** Проверяет корректное округление при float-артефактах (0.1 + 0.2 = 0.30000...4 → должно дать 30). */
    public function testOfMajorRoundsCorrectly(): void
    {
        $money = Money::ofMajor(0.1 + 0.2, Currency::USD);

        $this->assertSame(30, $money->minorUnits);
    }

    /** Проверяет создание отрицательной суммы через ofMajor (например, для возврата). */
    public function testOfMajorWithNegativeAmount(): void
    {
        $money = Money::ofMajor(-7.55, 'EUR');

        $this->assertSame(-755, $money->minorUnits);
    }

    /** Проверяет выброс InvalidArgumentException при передаче неизвестного строкового кода валюты. */
    #[DataProvider('invalidCurrencyProvider')]
    public function testOfMajorThrowsOnUnknownCurrencyString(string $currency): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Unknown currency: {$currency}");

        Money::ofMajor(10.00, $currency);
    }

    /** Набор невалидных строковых кодов валют для data provider. */
    /** @return array<array-key, array{0: string}> */
    public static function invalidCurrencyProvider(): array
    {
        return [
            [''],
            ['usd'],
            ['ABC'],
            ['123'],
        ];
    }

    /** Проверяет базовое сложение двух положительных сумм в одной валюте. */
    public function testAddSumsTwoAmounts(): void
    {
        $a = Money::ofMinor(1000, Currency::USD);
        $b = Money::ofMinor(500, Currency::USD);

        $result = $a->add($b);

        $this->assertSame(1500, $result->minorUnits);
        $this->assertSame(Currency::USD, $result->currency);
    }

    /** Проверяет иммутабельность: add() не изменяет исходный объект. */
    public function testAddDoesNotMutateOriginal(): void
    {
        $a = Money::ofMinor(1000, Currency::USD);
        $b = Money::ofMinor(500, Currency::USD);

        $a->add($b);

        $this->assertSame(1000, $a->minorUnits);
    }

    /** Проверяет, что сложение с нулём возвращает ту же сумму. */
    public function testAddWithZero(): void
    {
        $a = Money::ofMinor(777, Currency::RUB);
        $zero = Money::ofMinor(0, Currency::RUB);

        $result = $a->add($zero);

        $this->assertSame(777, $result->minorUnits);
    }

    /** Проверяет сложение положительной и отрицательной сумм (эквивалент вычитания). */
    public function testAddNegativeToPositive(): void
    {
        $a = Money::ofMinor(1000, Currency::USD);
        $b = Money::ofMinor(-300, Currency::USD);

        $result = $a->add($b);

        $this->assertSame(700, $result->minorUnits);
    }

    /** Проверяет выброс InvalidArgumentException при сложении сумм в разных валютах. */
    public function testAddThrowsOnDifferentCurrencies(): void
    {
        $usd = Money::ofMinor(1000, Currency::USD);
        $rub = Money::ofMinor(500, Currency::RUB);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot mix USD and RUB');

        $usd->add($rub);
    }

    /** Проверяет базовое вычитание двух положительных сумм в одной валюте. */
    public function testSubtractWorksCorrectly(): void
    {
        $a = Money::ofMinor(1000, Currency::USD);
        $b = Money::ofMinor(350, Currency::USD);

        $result = $a->subtract($b);

        $this->assertSame(650, $result->minorUnits);
    }

    /** Проверяет, что вычитание большей суммы из меньшей даёт отрицательный результат. */
    public function testSubtractCanProduceNegativeResult(): void
    {
        $a = Money::ofMinor(100, Currency::EUR);
        $b = Money::ofMinor(300, Currency::EUR);

        $result = $a->subtract($b);

        $this->assertSame(-200, $result->minorUnits);
        $this->assertTrue($result->isNegative());
    }

    /** Проверяет иммутабельность: subtract() не изменяет исходный объект. */
    public function testSubtractDoesNotMutateOriginal(): void
    {
        $a = Money::ofMinor(1000, Currency::USD);
        $b = Money::ofMinor(500, Currency::USD);

        $a->subtract($b);

        $this->assertSame(1000, $a->minorUnits);
    }

    /** Проверяет выброс InvalidArgumentException при вычитании сумм в разных валютах. */
    public function testSubtractThrowsOnDifferentCurrencies(): void
    {
        $eur = Money::ofMinor(1000, Currency::EUR);
        $usd = Money::ofMinor(500, Currency::USD);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot mix EUR and USD');

        $eur->subtract($usd);
    }

    /** Проверяет, что положительная сумма не считается нулевой. */
    public function testIsZeroReturnsFalseForPositive(): void
    {
        $money = Money::ofMinor(1, Currency::USD);

        $this->assertFalse($money->isZero());
    }

    /** Проверяет, что отрицательная сумма не считается нулевой. */
    public function testIsZeroReturnsFalseForNegative(): void
    {
        $money = Money::ofMinor(-1, Currency::USD);

        $this->assertFalse($money->isZero());
    }

    /** Проверяет, что положительная сумма не является отрицательной. */
    public function testIsNegativeReturnsFalseForPositive(): void
    {
        $money = Money::ofMinor(100, Currency::RUB);

        $this->assertFalse($money->isNegative());
    }

    /** Проверяет, что ноль не является отрицательной суммой. */
    public function testIsNegativeReturnsFalseForZero(): void
    {
        $money = Money::ofMinor(0, Currency::RUB);

        $this->assertFalse($money->isNegative());
    }

    /** Проверяет конвертацию минимальных единиц в дробное число через различные значения. */
    #[DataProvider('amountProvider')]
    public function testAmountReturnsFloat(int $minorUnits, float $expected): void
    {
        $money = Money::ofMinor($minorUnits, Currency::USD);

        $this->assertSame($expected, $money->amount());
    }

    /** Набор пар (минимальные единицы → ожидаемый float) для data provider. */
    /** @return array<array-key, array{int, float}> */
    public static function amountProvider(): array
    {
        return [
            [0, 0.0],
            [1, 0.01],
            [1999, 19.99],
            [10000, 100.0],
            [-550, -5.5],
            [123456789, 1234567.89],
        ];
    }

    /** Проверяет форматирование положительной суммы в строку вида «19.99 USD». */
    public function testToStringFormatsCorrectly(): void
    {
        $money = Money::ofMinor(1999, Currency::USD);

        $this->assertSame('19.99 USD', $money->toString());
    }

    /** Проверяет форматирование отрицательной суммы со знаком минус. */
    public function testToStringWithNegativeAmount(): void
    {
        $money = Money::ofMinor(-350, Currency::RUB);

        $this->assertSame('-3.50 RUB', $money->toString());
    }

    /** Проверяет форматирование нулевой суммы — всегда два знака после запятой. */
    public function testToStringWithZero(): void
    {
        $money = Money::ofMinor(0, Currency::EUR);

        $this->assertSame('0.00 EUR', $money->toString());
    }

    /** Проверяет, что магический __toString() делегирует в toString() и возвращает тот же результат. */
    public function testMagicToStringDelegatesToToString(): void
    {
        $money = Money::ofMinor(1999, Currency::USD);

        $this->assertSame($money->toString(), (string) $money);
    }

    /** Проверяет корректную интерполяцию объекта в строку через магию __toString. */
    public function testStringInterpolation(): void
    {
        $money = Money::ofMinor(4200, Currency::EUR);

        $this->assertSame('Total: 42.00 EUR', "Total: {$money}");
    }

    /** Проверяет, что equals возвращает true для одинаковых сумм в одной валюте. */
    public function testEqualsReturnsTrueForIdenticalValues(): void
    {
        $a = Money::ofMinor(1999, Currency::USD);
        $b = Money::ofMinor(1999, Currency::USD);

        $this->assertTrue($a->equals($b));
    }

    /** Проверяет, что equals возвращает false при разном количестве минимальных единиц. */
    public function testEqualsReturnsFalseForDifferentAmount(): void
    {
        $a = Money::ofMinor(1999, Currency::USD);
        $b = Money::ofMinor(2000, Currency::USD);

        $this->assertFalse($a->equals($b));
    }

    /** Проверяет, что equals возвращает false при разных валютах даже с одинаковой суммой. */
    public function testEqualsReturnsFalseForDifferentCurrency(): void
    {
        $a = Money::ofMinor(1999, Currency::USD);
        $b = Money::ofMinor(1999, Currency::EUR);

        $this->assertFalse($a->equals($b));
    }

    /** Проверяет, что объект равен самому себе. */
    public function testEqualsWithSameInstance(): void
    {
        $money = Money::ofMinor(500, Currency::RUB);

        $this->assertTrue($money->equals($money));
    }

    /** Проверяет, что арифметические операции возвращают новые объекты, а не мутируют существующие. */
    public function testOperationsReturnNewInstances(): void
    {
        $a = Money::ofMinor(1000, Currency::USD);
        $b = Money::ofMinor(500, Currency::USD);

        $sum = $a->add($b);
        $diff = $a->subtract($b);

        $this->assertNotSame($a, $sum);
        $this->assertNotSame($a, $diff);
        $this->assertNotSame($b, $sum);
        $this->assertNotSame($b, $diff);
    }

    /** Проверяет цепочку операций: вычитание и сложение подряд дают корректный результат. */
    public function testChainOperations(): void
    {
        $result = Money::ofMajor(100.00, 'USD')
            ->subtract(Money::ofMinor(2500, Currency::USD))
            ->add(Money::ofMinor(100, Currency::USD));

        $this->assertSame(7600, $result->minorUnits);
        $this->assertSame('76.00 USD', $result->toString());
    }

    /** Проверяет, что ofMajor и ofMinor с эквивалентными значениями создают равные объекты. */
    public function testOfMajorAndOfMinorProduceEqualResults(): void
    {
        $fromMajor = Money::ofMajor(19.99, Currency::USD);
        $fromMinor = Money::ofMinor(1999, Currency::USD);

        $this->assertTrue($fromMajor->equals($fromMinor));
    }

    /** Проверяет, что вычитание суммы из самой себя даёт ноль. */
    public function testSubtractSameAmountGivesZero(): void
    {
        $money = Money::ofMinor(5000, Currency::EUR);

        $result = $money->subtract($money);

        $this->assertTrue($result->isZero());
        $this->assertFalse($result->isNegative());
    }
}
