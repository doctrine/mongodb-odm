<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Types;

use DateTime;
use DateTimeImmutable;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\Mapping\MappingException;
use Doctrine\ODM\MongoDB\Tests\BaseTestCase;
use Doctrine\ODM\MongoDB\Tests\CaptureDeprecationMessages;
use Doctrine\ODM\MongoDB\Types\FloatType;
use Doctrine\ODM\MongoDB\Types\IntType;
use Doctrine\ODM\MongoDB\Types\InvalidTypeException;
use Doctrine\ODM\MongoDB\Types\StringType;
use Doctrine\ODM\MongoDB\Types\Type;
use Doctrine\ODM\MongoDB\Types\TypeRegistry;
use Generator;
use MongoDB\BSON\Binary;
use MongoDB\BSON\Decimal128;
use MongoDB\BSON\Int64;
use MongoDB\BSON\MaxKey;
use MongoDB\BSON\MinKey;
use MongoDB\BSON\ObjectId;
use MongoDB\BSON\Timestamp;
use MongoDB\BSON\UTCDateTime;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Uid\UuidV4;

use function get_debug_type;
use function hex2bin;
use function md5;
use function sprintf;
use function str_pad;
use function str_repeat;
use function time;

use const STR_PAD_LEFT;

class TypeTest extends BaseTestCase
{
    use CaptureDeprecationMessages;

    private TypeRegistry $registry;

    private ClassMetadata $class;

    public function setUp(): void
    {
        parent::setUp();

        $this->registry = new TypeRegistry();
        $this->class    = new ClassMetadata(TypeTestDocument::class);
        $this->class->setTypeRegistry($this->registry);
    }

    #[DataProvider('provideTypes')]
    public function testConversion(string $typeName, mixed $phpValue, mixed $bsonValue = null): void
    {
        $bsonValue ??= $phpValue;
        $type        = $this->registry->get($typeName);

        self::assertSameTypeAndValue($phpValue, $type->convertToPHPValue($bsonValue));
        self::assertSameTypeAndValue($bsonValue, $type->convertToDatabaseValue($phpValue));
    }

    #[DataProvider('provideTypes')]
    public function testConversionWithClosureToPHP(string $typeIdentifier, mixed $expectedValue, mixed $value = null): void
    {
        $this->class->mapField([
            'fieldName' => 'theField',
            'type' => $typeIdentifier,
        ]);

        $value   ??= $expectedValue;
        $fieldName = 'theField';
        $return    = $this;
        eval(Type::getType($typeIdentifier)->closureToPHP());

        self::assertSameTypeAndValue($expectedValue, $return);
    }

    public static function provideTypes(): Generator
    {
        yield 'id' => [Type::ID, '507f1f77bcf86cd799439011', new ObjectId('507f1f77bcf86cd799439011')];
        yield 'intId' => [Type::INTID, 1];
        yield 'customId' => [Type::CUSTOMID, (object) ['foo' => 'bar']];
        yield 'bool' => [Type::BOOL, true];
        yield 'boolean' => [Type::BOOLEAN, false];
        yield 'int' => [Type::INT, 69];
        yield 'integer' => [Type::INTEGER, 42];
        yield 'int64' => [Type::INT64, 100, new Int64(100)];
        yield 'float' => [Type::FLOAT, 3.14];
        yield 'string' => [Type::STRING, 'ohai'];
        yield 'minKey' => [Type::KEY, 0, new MinKey()];
        yield 'maxKey' => [Type::KEY, 1, new MaxKey()];
        yield 'timestamp' => [Type::TIMESTAMP, $t = time(), new Timestamp(0, $t)];
        yield 'binData' => [Type::BINDATA, 'foobarbaz', new Binary('foobarbaz', Binary::TYPE_GENERIC)];
        yield 'binDataFunc' => [Type::BINDATAFUNC, 'foobarbaz', new Binary('foobarbaz', Binary::TYPE_FUNCTION)];
        yield 'binDataByteArray' => [Type::BINDATABYTEARRAY, 'foobarbaz', new Binary('foobarbaz', Binary::TYPE_OLD_BINARY)];
        yield 'binDataUuid' => [Type::BINDATAUUID, 'testtesttesttest', new Binary('testtesttesttest', Binary::TYPE_OLD_UUID)];
        yield 'binDataUuidRFC4122' => [Type::BINDATAUUIDRFC4122, str_repeat('a', 16), new Binary(str_repeat('a', 16), Binary::TYPE_UUID)];
        yield 'binDataMD5' => [Type::BINDATAMD5, md5('ODM'), new Binary(md5('ODM'), Binary::TYPE_MD5)];
        yield 'binDataCustom' => [Type::BINDATACUSTOM, 'foobarbaz', new Binary('foobarbaz', Binary::TYPE_USER_DEFINED)];
        yield 'hash' => [Type::HASH, ['foo' => 'bar'], (object) ['foo' => 'bar']];
        yield 'collection' => [Type::COLLECTION, ['foo', 'bar']];
        yield 'objectId' => [Type::OBJECTID, '507f1f77bcf86cd799439011', new ObjectId('507f1f77bcf86cd799439011')];
        yield 'raw' => [Type::RAW, (object) ['foo' => 'bar']];
        yield 'decimal128' => [Type::DECIMAL128, '4.20', new Decimal128('4.20')];
        yield 'uuid' => [Type::UUID, new UuidV4('550e8400-e29b-41d4-a716-446655440000'), new Binary(hex2bin('550e8400e29b41d4a716446655440000'), Binary::TYPE_UUID)];
    }

    /** @param mixed $test */
    #[DataProvider('provideTypesForIdempotent')]
    public function testConversionIsIdempotent(string $type, $test): void
    {
        self::assertSameTypeAndValue($test, $this->registry->get($type)->convertToDatabaseValue($test));
    }

    public static function provideTypesForIdempotent(): Generator
    {
        yield 'id' => [Type::ID, new ObjectId()];
        yield 'date' => [Type::DATE, new UTCDateTime()];
        yield 'dateImmutable' => [Type::DATE_IMMUTABLE, new UTCDateTime()];
        yield 'int64' => [Type::INT64, new Int64(100)];
        yield 'timestamp' => [Type::TIMESTAMP, new Timestamp(0, time())];
        yield 'binData' => [Type::BINDATA, new Binary('foobarbaz', Binary::TYPE_GENERIC)];
        yield 'binDataFunc' => [Type::BINDATAFUNC, new Binary('foobarbaz', Binary::TYPE_FUNCTION)];
        yield 'binDataByteArray' => [Type::BINDATABYTEARRAY, new Binary('foobarbaz', Binary::TYPE_OLD_BINARY)];
        yield 'binDataUuid' => [Type::BINDATAUUID, new Binary('testtesttesttest', Binary::TYPE_OLD_UUID)];
        yield 'binDataUuidRFC4122' => [Type::BINDATAUUIDRFC4122, new Binary(str_repeat('a', 16), Binary::TYPE_UUID)];
        yield 'binDataMD5' => [Type::BINDATAMD5, new Binary(md5('ODM'), Binary::TYPE_MD5)];
        yield 'binDataCustom' => [Type::BINDATACUSTOM, new Binary('foobarbaz', Binary::TYPE_USER_DEFINED)];
        yield 'objectId' => [Type::OBJECTID, new ObjectId()];
        yield 'decimal128' => [Type::DECIMAL128, new Decimal128('4.20')];
    }

    public function testConvertDatePreservesMilliseconds(): void
    {
        $date         = new DateTime();
        $expectedDate = clone $date;

        $cleanMicroseconds = (int) $date->format('v') * 1000;
        $expectedDate->modify($date->format('H:i:s') . '.' . str_pad((string) $cleanMicroseconds, 6, '0', STR_PAD_LEFT));

        $registry = new TypeRegistry();
        $type     = $registry->get(Type::DATE);
        self::assertEquals($expectedDate, $type->convertToPHPValue($type->convertToDatabaseValue($date)));
    }

    public function testConvertDateImmutablePreservesMilliseconds(): void
    {
        $date = new DateTimeImmutable();

        $cleanMicroseconds = (int) $date->format('v') * 1000;
        $expectedDate      = $date->modify($date->format('H:i:s') . '.' . str_pad((string) $cleanMicroseconds, 6, '0', STR_PAD_LEFT));

        $type = $this->registry->get(Type::DATE_IMMUTABLE);
        self::assertEquals($expectedDate, $type->convertToPHPValue($type->convertToDatabaseValue($date)));
    }

    public function testConvertImmutableDate(): void
    {
        $date = new DateTimeImmutable('now');

        self::assertInstanceOf(UTCDateTime::class, Type::convertPHPToDatabaseValue($date));
    }

    #[DataProvider('provideTypeFromPHPVariable')]
    public function testGetTypeFromPHPVariable(?string $expectedType, mixed $variable): void
    {
        $type = $this->captureDeprecationMessages(static function () use ($variable): ?Type {
            return Type::getTypeFromPHPVariable($variable);
        }, $errors);

        if ($expectedType === null) {
            self::assertNull($type);
        } elseif ($type === null) {
            self::fail(sprintf('Type is null, expected "%s"', $expectedType));
        } else {
            $expectedType = $this->registry->get($expectedType);
            self::assertInstanceOf($expectedType::class, $type, $type::class);
        }

        self::assertSame(['Since doctrine/mongodb-odm 2.17: Type::getTypeFromPHPVariable() is deprecated without replacement.'], $errors);
    }

    public static function provideTypeFromPHPVariable(): Generator
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
        self::expectException(InvalidTypeException::class);
        self::expectExceptionMessage('Invalid type specified: "foo"');

        $this->registry->get('foo');
    }

    public function testDeprecatedMethods(): void
    {
        $this->captureDeprecationMessages(static function () {
            self::assertTrue(Type::hasType(Type::STRING));
            self::assertFalse(Type::hasType('non_existent_type'));
            self::assertInstanceOf(StringType::class, Type::getType(Type::STRING));
            self::assertInstanceOf(IntType::class, Type::getTypeFromPHPVariable(10));
            self::assertInstanceOf(UTCDateTime::class, Type::convertPHPToDatabaseValue(new DateTime()));
            Type::addType('custom_type', StringType::class);
            self::assertInstanceOf(StringType::class, Type::getType('custom_type'));
            Type::registerType('custom_type', FloatType::class);
            self::assertInstanceOf(FloatType::class, Type::getType('custom_type'));
            Type::overrideType('custom_type', IntType::class);
            self::assertInstanceOf(IntType::class, Type::getType('custom_type'));
            self::assertIsArray(Type::getTypesMap());

            try {
                Type::overrideType('non_existent_type', IntType::class);
                self::fail('Expected exception not thrown.');
            } catch (MappingException $e) {
                self::assertEquals(MappingException::typeNotFound('non_existent_type'), $e);
            }
        }, $errors);

        self::assertSame([
            'Since doctrine/mongodb-odm 2.17: Type::hasType() is deprecated, use $typeRegistry->has() instead.',
            'Since doctrine/mongodb-odm 2.17: Type::hasType() is deprecated, use $typeRegistry->has() instead.',
            'Since doctrine/mongodb-odm 2.17: Type::getType() is deprecated, use $typeRegistry->get() instead.',
            'Since doctrine/mongodb-odm 2.17: Type::getTypeFromPHPVariable() is deprecated without replacement.',
            'Since doctrine/mongodb-odm 2.17: Type::convertPHPToDatabaseValue() is deprecated without replacement.',
            'Since doctrine/mongodb-odm 2.17: Type::addType() is deprecated, use $typeRegistry->register() instead.',
            'Since doctrine/mongodb-odm 2.17: Type::getType() is deprecated, use $typeRegistry->get() instead.',
            'Since doctrine/mongodb-odm 2.17: Type::registerType() is deprecated, use $typeRegistry->register() instead.',
            'Since doctrine/mongodb-odm 2.17: Type::getType() is deprecated, use $typeRegistry->get() instead.',
            'Since doctrine/mongodb-odm 2.17: Type::overrideType() is deprecated, use $typeRegistry->register() instead.',
            'Since doctrine/mongodb-odm 2.17: Type::getType() is deprecated, use $typeRegistry->get() instead.',
            'Since doctrine/mongodb-odm 2.17: Type::getTypesMap() is deprecated and will be removed in 3.0. Iterate over the TypeRegistry instead.',
            'Since doctrine/mongodb-odm 2.17: Type::overrideType() is deprecated, use $typeRegistry->register() instead.',
        ], $errors);
    }

    private static function assertSameTypeAndValue(mixed $expected, mixed $actual): void
    {
        self::assertSame(get_debug_type($expected), get_debug_type($actual));
        self::assertEquals($expected, $actual);
    }
}


class TypeTestDocument
{
    /** @var string */
    public $id;

    /** @var mixed */
    public $theField;
}
