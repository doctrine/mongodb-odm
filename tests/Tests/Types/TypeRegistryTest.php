<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Types;

use DateTime;
use DateTimeImmutable;
use Doctrine\ODM\MongoDB\Tests\BaseTestCase;
use Doctrine\ODM\MongoDB\Types\FloatType;
use Doctrine\ODM\MongoDB\Types\IntType;
use Doctrine\ODM\MongoDB\Types\InvalidTypeException;
use Doctrine\ODM\MongoDB\Types\RawType;
use Doctrine\ODM\MongoDB\Types\Type;
use Doctrine\ODM\MongoDB\Types\TypeRegistry;
use Generator;
use InvalidArgumentException;
use MongoDB\BSON\UTCDateTime;
use PHPUnit\Framework\Attributes\DataProvider;
use stdClass;

use function sprintf;

class TypeRegistryTest extends BaseTestCase
{
    #[DataProvider('provideTypeToGuessFromValue')]
    public function testTypeFromVariable(?string $expectedType, mixed $variable): void
    {
        $registry = new TypeRegistry();
        $type     = $registry->guessTypeFromValue($variable);

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
        yield 'unknown object' => [
            null,
            new class () {
            },
        ];
    }

    public function testInvalidType(): void
    {
        $registry = new TypeRegistry();

        self::expectException(InvalidTypeException::class);
        self::expectExceptionMessage('Invalid type specified: "foo"');

        $registry->get('foo');
    }

    public function testRegister(): void
    {
        $registry = new TypeRegistry();
        self::assertFalse($registry->has('my_custom_type'));

        $registry->register('my_custom_type', IntType::class);
        self::assertTrue($registry->has('my_custom_type'));
        self::assertInstanceOf(IntType::class, $registry->get('my_custom_type'));
        self::assertSame($registry->get('my_custom_type'), $registry->get('my_custom_type'));

        $registry->register('my_custom_type', RawType::class);
        self::assertInstanceOf(RawType::class, $registry->get('my_custom_type'));
    }

    public function testRegisterTypeInstance(): void
    {
        $registry = new TypeRegistry();
        self::assertFalse($registry->has('my_custom_type'));

        $typeInstance = new IntType();
        $registry->register('my_custom_type', $typeInstance);
        self::assertTrue($registry->has('my_custom_type'));
        self::assertSame($typeInstance, $registry->get('my_custom_type'));

        // Replace it with a type by class name unsets the instance
        $registry->register('my_custom_type', FloatType::class);
        self::assertInstanceOf(FloatType::class, $registry->get('my_custom_type'));
    }

    public function testConvertToDatabaseValue(): void
    {
        $registry = new TypeRegistry();

        self::assertSame(42, $registry->convertToDatabaseValue(42));
        self::assertInstanceOf(UTCDateTime::class, $registry->convertToDatabaseValue(new DateTime()));

        // Not found
        $object = new class () {
        };
        self::assertSame($object, $registry->convertToDatabaseValue($object));
    }

    public function testSharedInstance(): void
    {
        self::assertSame(TypeRegistry::getSharedInstance(), TypeRegistry::getSharedInstance());
    }

    public function testRegisterRequiresATypeClassOrInstance(): void
    {
        $registry = new TypeRegistry();
        self::expectException(InvalidArgumentException::class);
        // @phpstan-ignore argument.type
        $registry->register('invalid_type', stdClass::class);
    }
}
