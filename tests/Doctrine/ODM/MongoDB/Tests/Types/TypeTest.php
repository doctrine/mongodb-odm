<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Types;

use DateTime;
use DateTimeImmutable;
use Doctrine\ODM\MongoDB\Tests\BaseTestCase;
use Doctrine\ODM\MongoDB\Types\Type;
use MongoDB\BSON\Binary;
use MongoDB\BSON\Decimal128;
use MongoDB\BSON\ObjectId;
use MongoDB\BSON\Timestamp;
use MongoDB\BSON\UTCDateTime;
use PHPUnit\Framework\Attributes\DataProvider;

use function md5;
use function str_pad;
use function str_repeat;
use function time;

use const STR_PAD_LEFT;

class TypeTest extends BaseTestCase
{
    /** @param mixed $test */
    #[DataProvider('provideTypes')]
    public function testConversion(Type $type, $test): void
    {
        self::assertEquals($test, $type->convertToPHPValue($type->convertToDatabaseValue($test)));
    }

    public static function provideTypes(): array
    {
        return [
            'id' => [Type::getType(Type::ID), '507f1f77bcf86cd799439011'],
            'intId' => [Type::getType(Type::INTID), 1],
            'customId' => [Type::getType(Type::CUSTOMID), (object) ['foo' => 'bar']],
            'bool' => [Type::getType(Type::BOOL), true],
            'boolean' => [Type::getType(Type::BOOLEAN), false],
            'int' => [Type::getType(Type::INT), 69],
            'integer' => [Type::getType(Type::INTEGER), 42],
            'float' => [Type::getType(Type::FLOAT), 3.14],
            'string' => [Type::getType(Type::STRING), 'ohai'],
            'minKey' => [Type::getType(Type::KEY), 0],
            'maxKey' => [Type::getType(Type::KEY), 1],
            'timestamp' => [Type::getType(Type::TIMESTAMP), time()],
            'binData' => [Type::getType(Type::BINDATA), 'foobarbaz'],
            'binDataFunc' => [Type::getType(Type::BINDATAFUNC), 'foobarbaz'],
            'binDataByteArray' => [Type::getType(Type::BINDATABYTEARRAY), 'foobarbaz'],
            'binDataUuid' => [Type::getType(Type::BINDATAUUID), 'testtesttesttest'],
            'binDataUuidRFC4122' => [Type::getType(Type::BINDATAUUIDRFC4122), str_repeat('a', 16)],
            'binDataMD5' => [Type::getType(Type::BINDATAMD5), md5('ODM')],
            'binDataCustom' => [Type::getType(Type::BINDATACUSTOM), 'foobarbaz'],
            'hash' => [Type::getType(Type::HASH), ['foo' => 'bar']],
            'collection' => [Type::getType(Type::COLLECTION), ['foo', 'bar']],
            'objectId' => [Type::getType(Type::OBJECTID), '507f1f77bcf86cd799439011'],
            'raw' => [Type::getType(Type::RAW), (object) ['foo' => 'bar']],
            'decimal128' => [Type::getType(Type::DECIMAL128), '4.20'],
        ];
    }

    /** @param mixed $test */
    #[DataProvider('provideTypesForIdempotent')]
    public function testConversionIsIdempotent(Type $type, $test): void
    {
        self::assertEquals($test, $type->convertToDatabaseValue($test));
    }

    public static function provideTypesForIdempotent(): array
    {
        return [
            'id' => [Type::getType(Type::ID), new ObjectId()],
            'date' => [Type::getType(Type::DATE), new UTCDateTime()],
            'dateImmutable' => [Type::getType(Type::DATE_IMMUTABLE), new UTCDateTime()],
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
}
