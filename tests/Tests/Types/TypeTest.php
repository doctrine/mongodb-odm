<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Types;

use DateTime;
use DateTimeImmutable;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\Tests\BaseTestCase;
use Doctrine\ODM\MongoDB\Types\InvalidTypeException;
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
use function str_pad;
use function str_repeat;
use function time;

use const STR_PAD_LEFT;

class TypeTest extends BaseTestCase
{
    private TypeRegistry $registry;

    private ClassMetadata $class;

    public function setUp(): void
    {
        parent::setUp();

        $this->registry = new TypeRegistry();
        $this->class    = new ClassMetadata(TypeTestDocument::class);
        $this->class->setTypeProvider($this->registry);
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
        eval($this->registry->get($typeIdentifier)->closureToPHP());

        self::assertSameTypeAndValue($expectedValue, $return);
    }

    public static function provideTypes(): Generator
    {
        yield 'id' => [Type::ID, '507f1f77bcf86cd799439011', new ObjectId('507f1f77bcf86cd799439011')];
        yield 'customId' => [Type::CUSTOMID, (object) ['foo' => 'bar']];
        yield 'bool' => [Type::BOOL, true];
        yield 'int' => [Type::INT, 69];
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

    #[DataProvider('provideTypesForIdempotent')]
    public function testConversionIsIdempotent(string $type, mixed $test): void
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

        self::assertInstanceOf(UTCDateTime::class, $this->registry->get(Type::DATE_IMMUTABLE)->convertToDatabaseValue($date));
    }

    public function testInvalidType(): void
    {
        self::expectException(InvalidTypeException::class);
        self::expectExceptionMessage('Invalid type specified: "foo"');

        $this->registry->get('foo');
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
