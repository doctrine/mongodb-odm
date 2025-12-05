<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Types;

use DateTime;
use DateTimeImmutable;
use Doctrine\ODM\MongoDB\Tests\BaseTestCase;
use Doctrine\ODM\MongoDB\Types\InvalidTypeException;
use Doctrine\ODM\MongoDB\Types\Type;
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
    #[DataProvider('provideTypes')]
    public function testConversion(string $typeName, mixed $phpValue, mixed $bsonValue = null): void
    {
        $bsonValue ??= $phpValue;
        $type        = Type::getType($typeName);

        self::assertSameTypeAndValue($phpValue, $type->convertToPHPValue($bsonValue));
        self::assertSameTypeAndValue($bsonValue, $type->convertToDatabaseValue($phpValue));
    }

    #[DataProvider('provideTypes')]
    public function testConversionWithClosureToPHP(string $typeIdentifier, mixed $expectedValue, mixed $value = null): void
    {
        $value ??= $expectedValue;
        $return = $this;
        eval(Type::getType($typeIdentifier)->closureToPHP());

        self::assertSameTypeAndValue($expectedValue, $return);
    }

    public static function provideTypes(): array
    {
        return [
            'id' => [Type::ID, '507f1f77bcf86cd799439011', new ObjectId('507f1f77bcf86cd799439011')],
            'intId' => [Type::INTID, 1],
            'customId' => [Type::CUSTOMID, (object) ['foo' => 'bar']],
            'bool' => [Type::BOOL, true],
            'boolean' => [Type::BOOLEAN, false],
            'int' => [Type::INT, 69],
            'integer' => [Type::INTEGER, 42],
            'int64' => [Type::INT64, 100, new Int64(100)],
            'float' => [Type::FLOAT, 3.14],
            'string' => [Type::STRING, 'ohai'],
            'minKey' => [Type::KEY, 0, new MinKey()],
            'maxKey' => [Type::KEY, 1, new MaxKey()],
            'timestamp' => [Type::TIMESTAMP, $t = time(), new Timestamp(0, $t)],
            'binData' => [Type::BINDATA, 'foobarbaz', new Binary('foobarbaz', Binary::TYPE_GENERIC)],
            'binDataFunc' => [Type::BINDATAFUNC, 'foobarbaz', new Binary('foobarbaz', Binary::TYPE_FUNCTION)],
            'binDataByteArray' => [Type::BINDATABYTEARRAY, 'foobarbaz', new Binary('foobarbaz', Binary::TYPE_OLD_BINARY)],
            'binDataUuid' => [Type::BINDATAUUID, 'testtesttesttest', new Binary('testtesttesttest', Binary::TYPE_OLD_UUID)],
            'binDataUuidRFC4122' => [Type::BINDATAUUIDRFC4122, str_repeat('a', 16), new Binary(str_repeat('a', 16), Binary::TYPE_UUID)],
            'binDataMD5' => [Type::BINDATAMD5, md5('ODM'), new Binary(md5('ODM'), Binary::TYPE_MD5)],
            'binDataCustom' => [Type::BINDATACUSTOM, 'foobarbaz', new Binary('foobarbaz', Binary::TYPE_USER_DEFINED)],
            'hash' => [Type::HASH, ['foo' => 'bar'], (object) ['foo' => 'bar']],
            'collection' => [Type::COLLECTION, ['foo', 'bar']],
            'objectId' => [Type::OBJECTID, '507f1f77bcf86cd799439011', new ObjectId('507f1f77bcf86cd799439011')],
            'raw' => [Type::RAW, (object) ['foo' => 'bar']],
            'decimal128' => [Type::DECIMAL128, '4.20', new Decimal128('4.20')],
            'uuid' => [Type::UUID, new UuidV4('550e8400-e29b-41d4-a716-446655440000'), new Binary(hex2bin('550e8400e29b41d4a716446655440000'), Binary::TYPE_UUID)],
        ];
    }

    /** @param mixed $test */
    #[DataProvider('provideTypesForIdempotent')]
    public function testConversionIsIdempotent(Type $type, $test): void
    {
        self::assertSameTypeAndValue($test, $type->convertToDatabaseValue($test));
    }

    public static function provideTypesForIdempotent(): array
    {
        return [
            'id' => [Type::getType(Type::ID), new ObjectId()],
            'date' => [Type::getType(Type::DATE), new UTCDateTime()],
            'dateImmutable' => [Type::getType(Type::DATE_IMMUTABLE), new UTCDateTime()],
            'int64' => [Type::getType(Type::INT64), new Int64(100)],
            'timestamp' => [Type::getType(Type::TIMESTAMP), new Timestamp(0, time())],
            'binData' => [Type::getType(Type::BINDATA), new Binary('foobarbaz', Binary::TYPE_GENERIC)],
            'binDataFunc' => [Type::getType(Type::BINDATAFUNC), new Binary('foobarbaz', Binary::TYPE_FUNCTION)],
            'binDataByteArray' => [Type::getType(Type::BINDATABYTEARRAY), new Binary('foobarbaz', Binary::TYPE_OLD_BINARY)],
            'binDataUuid' => [Type::getType(Type::BINDATAUUID), new Binary('testtesttesttest', Binary::TYPE_OLD_UUID)],
            'binDataUuidRFC4122' => [Type::getType(Type::BINDATAUUIDRFC4122), new Binary(str_repeat('a', 16), Binary::TYPE_UUID)],
            'binDataMD5' => [Type::getType(Type::BINDATAMD5), new Binary(md5('ODM'), Binary::TYPE_MD5)],
            'binDataCustom' => [Type::getType(Type::BINDATACUSTOM), new Binary('foobarbaz', Binary::TYPE_USER_DEFINED)],
            'objectId' => [Type::getType(Type::OBJECTID), new ObjectId()],
            'decimal128' => [Type::getType(Type::DECIMAL128), new Decimal128('4.20')],
        ];
    }

    public function testConvertDatePreservesMilliseconds(): void
    {
        $date         = new DateTime();
        $expectedDate = clone $date;

        $cleanMicroseconds = (int) $date->format('v') * 1000;
        $expectedDate->modify($date->format('H:i:s') . '.' . str_pad((string) $cleanMicroseconds, 6, '0', STR_PAD_LEFT));

        $type = Type::getType(Type::DATE);
        self::assertEquals($expectedDate, $type->convertToPHPValue($type->convertToDatabaseValue($date)));
    }

    public function testConvertDateImmutablePreservesMilliseconds(): void
    {
        $date = new DateTimeImmutable();

        $cleanMicroseconds = (int) $date->format('v') * 1000;
        $expectedDate      = $date->modify($date->format('H:i:s') . '.' . str_pad((string) $cleanMicroseconds, 6, '0', STR_PAD_LEFT));

        $type = Type::getType(Type::DATE_IMMUTABLE);
        self::assertEquals($expectedDate, $type->convertToPHPValue($type->convertToDatabaseValue($date)));
    }

    public function testConvertImmutableDate(): void
    {
        $date = new DateTimeImmutable('now');

        self::assertInstanceOf(UTCDateTime::class, Type::convertPHPToDatabaseValue($date));
    }

    #[DataProvider('provideTypeFromPHPVariable')]
    public function testGetTypeFromPHPVariable(?Type $expectedType, mixed $variable): void
    {
        $type = Type::getTypeFromPHPVariable($variable);

        if ($expectedType === null) {
            self::assertNull($type);
        } elseif ($type === null) {
            self::fail(sprintf('Type is null, expected "%s"', $expectedType::class));
        } else {
            self::assertInstanceOf($expectedType::class, $type, $type::class);
        }
    }

    public static function provideTypeFromPHPVariable(): array
    {
        return [
            'null' => [null, null],
            'bool' => [Type::getType(Type::BOOL), true],
            'int' => [Type::getType(Type::INT), 1],
            'float' => [Type::getType(Type::FLOAT), 3.14],
            'string' => [Type::getType(Type::STRING), 'ohai'],
            'DateTime' => [Type::getType(Type::DATE), new DateTime()],
            'DateTimeImmutable' => [Type::getType(Type::DATE_IMMUTABLE), new DateTimeImmutable()],
            'unknown object' => [
                null,
                new class () {
                },
            ],
        ];
    }

    public function testInvalidType(): void
    {
        self::expectException(InvalidTypeException::class);
        self::expectExceptionMessage('Invalid type specified: "foo"');

        Type::getType('foo');
    }

    private static function assertSameTypeAndValue(mixed $expected, mixed $actual): void
    {
        self::assertSame(get_debug_type($expected), get_debug_type($actual));
        self::assertEquals($expected, $actual);
    }
}
