<?php

namespace Doctrine\ODM\MongoDB\Tests\Types;

use Doctrine\MongoDB\GridFSFile;
use Doctrine\ODM\MongoDB\Types\Type;
use MongoBinData;

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
            array(Type::getType(Type::INCREMENT), 1),
            array(Type::getType(Type::INCREMENT), 1.1),
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
            array(Type::getType(Type::ID), new \MongoId()),
            array(Type::getType(Type::DATE), new \MongoDate()),
            array(Type::getType(Type::TIMESTAMP), new \MongoTimestamp()),
            array(Type::getType(Type::BINDATA), new MongoBinData('foobarbaz', 0)),
            array(Type::getType(Type::BINDATAFUNC), new MongoBinData('foobarbaz', MongoBinData::FUNC)),
            array(Type::getType(Type::BINDATABYTEARRAY), new MongoBinData('foobarbaz', MongoBinData::BYTE_ARRAY)),
            array(Type::getType(Type::BINDATAUUID), new MongoBinData("7f1c6d80-3e0b-11e5-b8ed-0002a5d5c51b", MongoBinData::UUID)),
            array(Type::getType(Type::BINDATAUUIDRFC4122), new MongoBinData(str_repeat('a', 16), 4)),
            array(Type::getType(Type::BINDATAMD5), new MongoBinData(md5('ODM'), MongoBinData::MD5)),
            array(Type::getType(Type::BINDATACUSTOM), new MongoBinData('foobarbaz', MongoBinData::CUSTOM)),
            array(Type::getType(Type::OBJECTID), new \MongoId()),
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

        $this->assertInstanceOf('\MongoDate', Type::convertPHPToDatabaseValue($date));
    }
}
