<?php

namespace Doctrine\ODM\MongoDB\Tests\Types;

use Doctrine\MongoDB\GridFSFile;
use Doctrine\ODM\MongoDB\Types\Type;

class TypeTest extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    /**
     * @dataProvider provideTypes
     */
    public function testConversion(Type $type, $test)
    {
        $this->assertEquals($test, $type->convertToPHPValue($type->convertToDatabaseValue($test)));
    }

    public function provideTypes()
    {
        return array(
            array(Type::getType(Type::ID), "507f1f77bcf86cd799439011"),
            array(Type::getType(Type::INTID), 1),
            array(Type::getType(Type::CUSTOMID), (object) array('foo' => 'bar')),
            array(Type::getType(Type::BOOL), true),
            array(Type::getType(Type::BOOLEAN), false),
            array(Type::getType(Type::INT), 69),
            array(Type::getType(Type::INTEGER), 42),
            array(Type::getType(Type::FLOAT), 3.14),
            array(Type::getType(Type::STRING), 'ohai'),
            array(Type::getType(Type::KEY), 0),
            array(Type::getType(Type::KEY), 1),
            array(Type::getType(Type::TIMESTAMP), time()),
            array(Type::getType(Type::BINDATA), 'foobarbaz'),
            array(Type::getType(Type::BINDATAFUNC), 'foobarbaz'),
            array(Type::getType(Type::BINDATABYTEARRAY), 'foobarbaz'),
            array(Type::getType(Type::BINDATAUUID), "7f1c6d80-3e0b-11e5-b8ed-0002a5d5c51b"),
            array(Type::getType(Type::BINDATAUUIDRFC4122), str_repeat('a', 16)),
            array(Type::getType(Type::BINDATAMD5), md5('ODM')),
            array(Type::getType(Type::BINDATACUSTOM), 'foobarbaz'),
            array(Type::getType(Type::FILE), new GridFSFile()),
            array(Type::getType(Type::HASH), array('foo' => 'bar')),
            array(Type::getType(Type::COLLECTION), array('foo', 'bar')),
            array(Type::getType(Type::OBJECTID), "507f1f77bcf86cd799439011"),
            array(Type::getType(Type::RAW), (object) array('foo' => 'bar')),
       );
    }

    /**
     * @dataProvider provideTypesForIdempotent
     */
    public function testConversionIsIdempotent(Type $type, $test)
    {
        $this->assertEquals($test, $type->convertToDatabaseValue($test));
    }

    public function provideTypesForIdempotent()
    {
        return array(
            array(Type::getType(Type::ID), new \MongoDB\BSON\ObjectId()),
            array(Type::getType(Type::DATE), new \MongoDB\BSON\UTCDateTime()),
            array(Type::getType(Type::TIMESTAMP), new \MongoTimestamp()),
            array(Type::getType(Type::BINDATA), new \MongoDB\BSON\Binary('foobarbaz', \MongoDB\BSON\Binary::TYPE_GENERIC)),
            array(Type::getType(Type::BINDATAFUNC), new \MongoDB\BSON\Binary('foobarbaz', \MongoDB\BSON\Binary::TYPE_FUNCTION)),
            array(Type::getType(Type::BINDATABYTEARRAY), new \MongoDB\BSON\Binary('foobarbaz', \MongoDB\BSON\Binary::TYPE_OLD_BINARY)),
            array(Type::getType(Type::BINDATAUUID), new \MongoDB\BSON\Binary("7f1c6d80-3e0b-11e5-b8ed-0002a5d5c51b", \MongoDB\BSON\Binary::TYPE_OLD_UUID)),
            array(Type::getType(Type::BINDATAUUIDRFC4122), new \MongoDB\BSON\Binary(str_repeat('a', 16), \MongoDB\BSON\Binary::TYPE_UUID)),
            array(Type::getType(Type::BINDATAMD5), new \MongoDB\BSON\Binary(md5('ODM'), \MongoDB\BSON\Binary::TYPE_MD5)),
            array(Type::getType(Type::BINDATACUSTOM), new \MongoDB\BSON\Binary('foobarbaz', \MongoDB\BSON\Binary::TYPE_USER_DEFINED)),
            array(Type::getType(Type::OBJECTID), new \MongoDB\BSON\ObjectId()),
        );
    }

    public function testConvertDatePreservesMilliseconds()
    {
        $date = new \DateTime();
        $expectedDate = clone $date;

        $cleanMicroseconds = (int) floor(((int) $date->format('u')) / 1000) * 1000;
        $expectedDate->modify($date->format('H:i:s') . '.' . str_pad($cleanMicroseconds, 6, '0', STR_PAD_LEFT));

        $type = Type::getType(Type::DATE);
        $this->assertEquals($expectedDate, $type->convertToPHPValue($type->convertToDatabaseValue($date)));
    }

    public function testConvertImmutableDate()
    {
        $date = new \DateTimeImmutable('now');

        $this->assertInstanceOf(\MongoDB\BSON\UTCDateTime::class, Type::convertPHPToDatabaseValue($date));
    }
}
