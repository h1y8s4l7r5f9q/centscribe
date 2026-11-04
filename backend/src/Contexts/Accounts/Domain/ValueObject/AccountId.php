<?php

declare(strict_types=1);

namespace App\Contexts\Accounts\Domain\ValueObject;

use App\SharedKernel\ValueObject\Uuid;

/**
 * Идентификатор счёта в контексте Accounts.
 */
final readonly class AccountId implements \Stringable
{
    private function __construct(
        private Uuid $uuid,
    ) {
    }

    /**
     * Генерирует новый идентификатор.
     */
    public static function generate(): self
    {
        return new self(Uuid::generate());
    }

    /**
     * Восстанавливает идентификатор из строки (чтение из БД).
     * Валидация формата делегируется в SharedKernel\Uuid.
     */
    public static function fromString(string $value): self
    {
        return new self(Uuid::fromString($value));
    }

    /**
     * Проверяет, указывают ли два идентификатора на один и тот же счёт.
     */
    public function equals(self $other): bool
    {
        return $this->uuid->equals($other->uuid);
    }

    /**
     * Возвращает строковое значение UUID (для БД, API-ответов).
     */
    public function value(): string
    {
        return $this->uuid->value;
    }

    /**
     * Строковое представление — для логов, sprintf, шаблонов.
     */
    public function __toString(): string
    {
        return $this->uuid->value;
    }
}
