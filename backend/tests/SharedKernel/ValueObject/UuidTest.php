<?php

declare(strict_types=1);

namespace Tests\SharedKernel\ValueObject;

use App\SharedKernel\ValueObject\Uuid;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[CoversClass(Uuid::class)]
#[Group('unit')]
final class UuidTest extends TestCase
{
    /**
     * generate() возвращает валидный UUID v7.
     * Версия «7» стоит на 13-м символе (после второго дефиса).
     */
    public function testGenerateProducesValidV7(): void
    {
        $uuid = Uuid::generate();

        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-7[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/',
            $uuid->value,
        );
    }

    /**
     * Два вызова generate() возвращают разные значения.
     */
    public function testGenerateProducesUniqueValues(): void
    {
        $first = Uuid::generate();
        $second = Uuid::generate();

        $this->assertNotSame($first->value, $second->value);
    }

    /**
     * fromString() принимает корректную строку UUID.
     */
    public function testFromStringAcceptsValidUuid(): void
    {
        $input = '01a1a63b-2f03-783f-8505-5f5244383ecf';

        $uuid = Uuid::fromString($input);

        $this->assertSame($input, $uuid->value);
    }

    /**
     * fromString() бросает InvalidArgumentException на мусор.
     */
    #[DataProvider('invalidUuidProvider')]
    public function testFromStringRejectsInvalidValue(string $garbage): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Некорректный UUID');

        Uuid::fromString($garbage);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function invalidUuidProvider(): array
    {
        return [
            'пустая строка' => [''],
            'случайный текст' => ['not-a-uuid'],
            'только цифры' => ['1234567890'],
            'неверная версия v4' => ['a3f1b2c4-5678-4abc-def0-1234567890ab'],
            'лишние символы' => ['01942a3b-7e0f-7abc-def0-1234567890ab-extra'],
        ];
    }

    /**
     * equals() возвращает true для одинаковых UUID, false для разных.
     */
    public function testEquals(): void
    {
        $original = Uuid::generate();
        $same = Uuid::fromString($original->value);
        $different = Uuid::generate();

        $this->assertTrue($original->equals($same));
        $this->assertFalse($original->equals($different));
    }

    /**
     * __toString() возвращает ту же строку, что и public property value.
     */
    public function testToString(): void
    {
        $uuid = Uuid::generate();

        $this->assertSame($uuid->value, (string) $uuid);
    }
}
