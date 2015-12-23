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
        return [
            [Type::getType(Type::ID), "507f1f77bcf86cd799439011"],
            [Type::getType(Type::INTID), 1],
            [Type::getType(Type::CUSTOMID), (object) ['foo' => 'bar']],
            [Type::getType(Type::BOOL), true],
            [Type::getType(Type::BOOLEAN), false],
            [Type::getType(Type::INT), 69],
            [Type::getType(Type::INTEGER), 42],
            [Type::getType(Type::FLOAT), 3.14],
            [Type::getType(Type::STRING), 'ohai'],
            [Type::getType(Type::DATE), new \DateTime()],
            [Type::getType(Type::KEY), 0],
            [Type::getType(Type::KEY), 1],
            [Type::getType(Type::TIMESTAMP), time()],
            [Type::getType(Type::BINDATA), 'foobarbaz'],
            [Type::getType(Type::BINDATAFUNC), 'foobarbaz'],
            [Type::getType(Type::BINDATABYTEARRAY), 'foobarbaz'],
            [Type::getType(Type::BINDATAUUID), "7f1c6d80-3e0b-11e5-b8ed-0002a5d5c51b"],
            [Type::getType(Type::BINDATAUUIDRFC4122), str_repeat('a', 16)],
            [Type::getType(Type::BINDATAMD5), md5('ODM')],
            [Type::getType(Type::BINDATACUSTOM), 'foobarbaz'],
            [Type::getType(Type::FILE), new GridFSFile()],
            [Type::getType(Type::HASH), ['foo' => 'bar']],
            [Type::getType(Type::COLLECTION), ['foo', 'bar']],
            [Type::getType(Type::INCREMENT), 1],
            [Type::getType(Type::INCREMENT), 1.1],
            [Type::getType(Type::OBJECTID), "507f1f77bcf86cd799439011"],
            [Type::getType(Type::RAW), (object) ['foo' => 'bar']],
        ];
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
        return [
            [Type::getType(Type::ID), new \MongoId()],
            [Type::getType(Type::DATE), new \MongoDate()],
            [Type::getType(Type::TIMESTAMP), new \MongoTimestamp()],
            [Type::getType(Type::BINDATA), new MongoBinData('foobarbaz', 0)],
            [Type::getType(Type::BINDATAFUNC), new MongoBinData('foobarbaz', MongoBinData::FUNC)],
            [Type::getType(Type::BINDATABYTEARRAY), new MongoBinData('foobarbaz', MongoBinData::BYTE_ARRAY)],
            [Type::getType(Type::BINDATAUUID), new MongoBinData("7f1c6d80-3e0b-11e5-b8ed-0002a5d5c51b", MongoBinData::UUID)],
            [Type::getType(Type::BINDATAUUIDRFC4122), new MongoBinData(str_repeat('a', 16), 4)],
            [Type::getType(Type::BINDATAMD5), new MongoBinData(md5('ODM'), MongoBinData::MD5)],
            [Type::getType(Type::BINDATACUSTOM), new MongoBinData('foobarbaz', MongoBinData::CUSTOM)],
            [Type::getType(Type::OBJECTID), new \MongoId()],
        ];
    }
}
