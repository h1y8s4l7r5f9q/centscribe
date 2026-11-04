<?php

declare(strict_types=1);

namespace Tests\Contexts\Accounts\Domain\ValueObject;

use App\Contexts\Accounts\Domain\ValueObject\AccountId;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[CoversClass(AccountId::class)]
#[Group('unit')]
final class AccountIdTest extends TestCase
{
    private const string UUID = '01a1a65d-7ecd-7108-b21d-6a5f0efd3983';

    /**
     * generate() создаёт два разных идентификатора.
     */
    public function testGenerateProducesUniqueValues(): void
    {
        $first = AccountId::generate();
        $second = AccountId::generate();

        $this->assertNotSame($first->value(), $second->value());
    }

    /**
     * fromString() восстанавливает идентификатор из валидной строки.
     */
    public function testFromStringRestoresIdentifier(): void
    {
        $id = AccountId::fromString(self::UUID);

        $this->assertSame(self::UUID, $id->value());
    }

    /**
     * Round-trip: generate → value() → fromString сохраняет равенство.
     */
    public function testFromStringRoundTrip(): void
    {
        $original = AccountId::generate();
        $restored = AccountId::fromString($original->value());

        $this->assertTrue($original->equals($restored));
    }

    /**
     * equals() возвращает true для одинаковых UUID, false для разных.
     */
    public function testEquals(): void
    {
        $a = AccountId::fromString(self::UUID);
        $b = AccountId::fromString(self::UUID);
        $c = AccountId::generate();

        $this->assertTrue($a->equals($b));
        $this->assertFalse($a->equals($c));
    }

    /**
     * value() и __toString() возвращают одну и ту же строку.
     */
    public function testValueAndToString(): void
    {
        $id = AccountId::fromString(self::UUID);

        $this->assertSame(self::UUID, $id->value());
        $this->assertSame($id->value(), (string) $id);
    }

    /**
     * Класс final readonly с приватным конструктором — создавать можно только через named constructors.
     */
    public function testClassIsFinalReadonlyWithPrivateConstructor(): void
    {
        $reflection = new \ReflectionClass(AccountId::class);

        $this->assertTrue($reflection->isFinal());
        $this->assertTrue($reflection->isReadOnly());

        $constructor = $reflection->getConstructor();
        $this->assertNotNull($constructor);
        $this->assertTrue($constructor->isPrivate());
    }
}
