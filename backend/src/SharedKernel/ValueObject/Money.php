<?php

declare(strict_types=1);

namespace App\SharedKernel\ValueObject;

use App\SharedKernel\Enum\Currency;

final readonly class Money implements \Stringable
{
    private function __construct(
        public int $minorUnits,
        public Currency $currency,
    ) {
    }

    /**
     * Создаёт Money из уже готового числа минимальных единиц (центов/копеек).
     * Используется при чтении из БД или из ответа платёжного шлюза.
     *
     * Пример: Money::ofMinor(1999, Currency::USD) → $19.99
     */
    public static function ofMinor(int $minorUnits, Currency $currency): self
    {
        return new self($minorUnits, $currency);
    }

    /**
     * Создаёт Money из привычного «человеческого» (мажорного) числа.
     * Автоматически пересчитывает в минимальные единицы (×100).
     * Валюту можно передать строкой — она будет распознана автоматически.
     *
     * Пример: Money::ofMajor(19.99, 'USD') → $19.99
     */
    public static function ofMajor(float|int $amount, Currency|string $currency): self
    {
        if (is_string($currency)) {
            $resolved = Currency::tryFrom($currency);

            if (null === $resolved) {
                throw new \InvalidArgumentException("Unknown currency: {$currency}");
            }

            $currency = $resolved;
        }

        return new self((int) round($amount * 100), $currency);
    }

    /**
     * Складывает две суммы. Валюты должны совпадать, иначе — исключение.
     * Возвращает новый Money, исходный не меняется.
     *
     * Пример: $10.00 + $5.00 → $15.00
     */
    public function add(self $other): self
    {
        $this->assertSameCurrency($other);

        return new self($this->minorUnits + $other->minorUnits, $this->currency);
    }

    /**
     * Вычитает одну сумму из другой. Валюты должны совпадать.
     * Результат может быть отрицательным (например, возврат или перерасход).
     * Возвращает новый Money.
     *
     * Пример: $10.00 − $3.50 → $6.50
     */
    public function subtract(self $other): self
    {
        $this->assertSameCurrency($other);

        return new self($this->minorUnits - $other->minorUnits, $this->currency);
    }

    /**
     * Проверяет, является ли сумма нулевой (0 копеек / 0 центов).
     */
    public function isZero(): bool
    {
        return 0 === $this->minorUnits;
    }

    /**
     * Проверяет, является ли сумма отрицательной.
     * Полезно для проверки перерасхода или возврата.
     */
    public function isNegative(): bool
    {
        return $this->minorUnits < 0;
    }

    /**
     * Возвращает сумму как обычное число (float) для отображения.
     * 1999 → 19.99.
     */
    public function amount(): float
    {
        return $this->minorUnits / 100;
    }

    /**
     * Форматирует сумму в читаемую строку: «19.99 USD».
     * Удобно для логов, email, отчётов.
     */
    public function toString(): string
    {
        return sprintf('%.2f %s', $this->amount(), $this->currency->value);
    }

    /**
     * Проверяет, равны ли две суммы: одинаковые минимальные единицы И одна валюта.
     */
    public function equals(self $other): bool
    {
        return $this->minorUnits === $other->minorUnits
            && $this->currency === $other->currency;
    }

    /**
     * Защита от арифметики между разными валютами.
     * Бросает исключение, если валюты не совпадают:
     * «Cannot mix USD and RUB».
     */
    private function assertSameCurrency(self $other): void
    {
        if ($this->currency !== $other->currency) {
            throw new \InvalidArgumentException("Cannot mix {$this->currency->value} and {$other->currency->value}");
        }
    }

    /**
     * Магический «вход» для строкового контекста: echo, конкатенация, sprintf(%s), шаблоны.
     * Делегирует в toString(), чтобы форматирование было в одном месте.
     *
     * Пример: "Итого: {$money}" → «Итого: 19.99 USD»
     */
    public function __toString(): string
    {
        return $this->toString();
    }
}
