<?php

declare(strict_types=1);

namespace App\SharedKernel\ValueObject;

use Symfony\Component\Uid\Uuid as SymfonyUuid;

/**
 * Generic UUID value object.
 *
 * Отвечает за МЕХАНИКУ: как создать, валидировать, форматировать UUID.
 * Не несёт доменного смысла — это infrastructure-примитив,
 * доступный всем bounded contexts через SharedKernel.
 */
final readonly class Uuid implements \Stringable
{
    private function __construct(
        public string $value,
    ) {
    }

    /**
     * Генерирует UUID v7 (time-ordered).
     * Первые 48 бит — Unix timestamp в мс → B-tree индексы растут последовательно.
     */
    public static function generate(): self
    {
        return new self(SymfonyUuid::v7()->toRfc4122());
    }

    /**
     * Создаёт из уже существующей строки (чтение из БД, JSON и т. д.).
     * Валидирует формат — мусор не пропустит.
     */
    public static function fromString(string $value): self
    {
        if (!SymfonyUuid::isValid($value)) {
            throw new \InvalidArgumentException(sprintf('Некорректный UUID: «%s».', $value));
        }

        return new self($value);
    }

    /**
     * Равенство двух идентификаторов.
     */
    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    /**
     * Строковое представление (для логов, JSON, URL).
     */
    public function __toString(): string
    {
        return $this->value;
    }
}
