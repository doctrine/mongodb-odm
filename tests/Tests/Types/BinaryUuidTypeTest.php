<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Types;

use Doctrine\ODM\MongoDB\Types\Type;
use InvalidArgumentException;
use MongoDB\BSON\Binary;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\UuidV4;
use Throwable;

class BinaryUuidTypeTest extends TestCase
{
    public function testConvertToDatabaseValue(): void
    {
        $type       = Type::getType(Type::UUID);
        $uuid       = new UuidV4();
        $stringUuid = $uuid->toRfc4122();
        $binaryUuid = new Binary($uuid->toBinary(), Binary::TYPE_UUID);

        self::assertNull($type->convertToDatabaseValue(null), 'null is not converted');
        self::assertEquals($binaryUuid, $type->convertToDatabaseValue($uuid), 'Uuid objects are converted to Binary objects');
        self::assertEquals($binaryUuid, $type->convertToDatabaseValue($stringUuid), 'String UUIDs are converted to Binary objects');
        self::assertSame($binaryUuid, $type->convertToDatabaseValue($binaryUuid), 'Binary UUIDs are returned as is');
    }

    public function testConvertInvalidUuid(): void
    {
        $type = Type::getType(Type::UUID);

        $this->expectException(InvalidArgumentException::class);
        $type->convertToDatabaseValue('invalid');
    }

    public function testConvertToPHPValue(): void
    {
        $type       = Type::getType(Type::UUID);
        $uuid       = new UuidV4();
        $binaryUuid = new Binary($uuid->toBinary(), Binary::TYPE_UUID);

        self::assertEquals($uuid, $type->convertToPHPValue($binaryUuid), 'Binary UUIDs are converted to Uuid objects');
        self::assertSame($uuid, $type->convertToPHPValue($uuid), 'Uuid objects are returned as is');
    }

    public function testConvertInvalidBinaryUuid(): void
    {
        $type = Type::getType(Type::UUID);

        $this->expectException(InvalidArgumentException::class);
        $type->convertToPHPValue(new Binary('invalid', Binary::TYPE_UUID));
    }

    public function testConvertInvalidBinary(): void
    {
        $type = Type::getType(Type::UUID);

        $this->expectException(Throwable::class);
        $type->convertToPHPValue(new Binary('invalid', Binary::TYPE_GENERIC));
    }

    public function testClosureToPhp(): void
    {
        $type       = Type::getType(Type::UUID);
        $uuid       = new UuidV4();
        $binaryUuid = new Binary($uuid->toBinary(), Binary::TYPE_UUID);

        $convertToPHPValue = static function ($value) use ($type) {
            $return = null;
            eval($type->closureToPHP());

            return $return;
        };

        self::assertEquals($uuid, $convertToPHPValue($binaryUuid), 'Binary UUIDs are converted to Uuid objects');
        self::assertSame($uuid, $convertToPHPValue($uuid), 'Uuid objects are returned as is');
    }
}
