<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Types;

use DateTime;
use DateTimeImmutable;
use Doctrine\ODM\MongoDB\Tests\BaseTestCase;
use Doctrine\ODM\MongoDB\Types\Type;
use Doctrine\ODM\MongoDB\Types\TypeGuesser;
use Doctrine\ODM\MongoDB\Types\TypeRegistry;
use Generator;
use MongoDB\BSON\UTCDateTime;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Uid\Uuid;

use function sprintf;

class TypeGuesserTest extends BaseTestCase
{
    #[DataProvider('provideTypeToGuessFromValue')]
    public function testTypeFromVariable(?string $expectedType, mixed $variable): void
    {
        $registry = new TypeRegistry();
        $type     = (new TypeGuesser($registry))->guessTypeFromValue($variable);

        if ($expectedType === null) {
            self::assertNull($type);
        } elseif ($type === null) {
            self::fail(sprintf('Type is null, expected "%s"', $expectedType));
        } else {
            self::assertSame($registry->get($expectedType), $type);
        }
    }

    public static function provideTypeToGuessFromValue(): Generator
    {
        yield 'null' => [null, null];
        yield 'bool' => [Type::BOOL, true];
        yield 'int' => [Type::INT, 1];
        yield 'float' => [Type::FLOAT, 3.14];
        yield 'string' => [Type::STRING, 'ohai'];
        yield 'DateTime' => [Type::DATE, new DateTime()];
        yield 'DateTimeImmutable' => [Type::DATE_IMMUTABLE, new DateTimeImmutable()];
        yield 'Uuid' => [Type::UUID, Uuid::v7()];
        yield 'unknown object' => [
            null,
            new class () {
            },
        ];
    }

    public function testTypeFromVariableUsesTheClassNameAsTypeName(): void
    {
        $registry = new TypeRegistry();
        $value    = new class () {
        };
        $registry->register($value::class, StubType::class);

        self::assertSame($registry->get($value::class), (new TypeGuesser($registry))->guessTypeFromValue($value));
    }

    public function testConvertToDatabaseValue(): void
    {
        $guesser = new TypeGuesser(new TypeRegistry());

        self::assertSame(42, $guesser->convertToDatabaseValue(42));
        self::assertInstanceOf(UTCDateTime::class, $guesser->convertToDatabaseValue(new DateTime()));

        // Not found
        $object = new class () {
        };
        self::assertSame($object, $guesser->convertToDatabaseValue($object));
    }
}

class StubType extends Type
{
}
