<?php

namespace Doctrine\ODM\MongoDB\Tests\Types;

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
            'id' => array(Type::getType(Type::ID), "507f1f77bcf86cd799439011"),
            'intId' => array(Type::getType(Type::INTID), 1),
            'customId' => array(Type::getType(Type::CUSTOMID), (object) array('foo' => 'bar')),
            'bool' => array(Type::getType(Type::BOOL), true),
            'boolean' => array(Type::getType(Type::BOOLEAN), false),
            'int' => array(Type::getType(Type::INT), 69),
            'integer' => array(Type::getType(Type::INTEGER), 42),
            'float' => array(Type::getType(Type::FLOAT), 3.14),
            'string' => array(Type::getType(Type::STRING), 'ohai'),
            'minKey' => array(Type::getType(Type::KEY), 0),
            'maxKey' => array(Type::getType(Type::KEY), 1),
            'timestamp' => array(Type::getType(Type::TIMESTAMP), time()),
            'binData' => array(Type::getType(Type::BINDATA), 'foobarbaz'),
            'binDataFunc' => array(Type::getType(Type::BINDATAFUNC), 'foobarbaz'),
            'binDataByteArray' => array(Type::getType(Type::BINDATABYTEARRAY), 'foobarbaz'),
            'binDataUuid' => array(Type::getType(Type::BINDATAUUID), "testtesttesttest"),
            'binDataUuidRFC4122' => array(Type::getType(Type::BINDATAUUIDRFC4122), str_repeat('a', 16)),
            'binDataMD5' => array(Type::getType(Type::BINDATAMD5), md5('ODM')),
            'binDataCustom' => array(Type::getType(Type::BINDATACUSTOM), 'foobarbaz'),
            'hash' => array(Type::getType(Type::HASH), array('foo' => 'bar')),
            'collection' => array(Type::getType(Type::COLLECTION), array('foo', 'bar')),
            'objectId' => array(Type::getType(Type::OBJECTID), "507f1f77bcf86cd799439011"),
            'raw' => array(Type::getType(Type::RAW), (object) array('foo' => 'bar')),
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
            'id' => array(Type::getType(Type::ID), new \MongoDB\BSON\ObjectId()),
            'date' => array(Type::getType(Type::DATE), new \MongoDB\BSON\UTCDateTime()),
            'timestamp' => array(Type::getType(Type::TIMESTAMP), new \MongoDB\BSON\Timestamp(0, time())),
            'binData' => array(Type::getType(Type::BINDATA), new \MongoDB\BSON\Binary('foobarbaz', \MongoDB\BSON\Binary::TYPE_GENERIC)),
            'binDataFunc' => array(Type::getType(Type::BINDATAFUNC), new \MongoDB\BSON\Binary('foobarbaz', \MongoDB\BSON\Binary::TYPE_FUNCTION)),
            'binDataByteArray' => array(Type::getType(Type::BINDATABYTEARRAY), new \MongoDB\BSON\Binary('foobarbaz', \MongoDB\BSON\Binary::TYPE_OLD_BINARY)),
            'binDataUuid' => array(Type::getType(Type::BINDATAUUID), new \MongoDB\BSON\Binary("testtesttesttest", \MongoDB\BSON\Binary::TYPE_OLD_UUID)),
            'binDataUuidRFC4122' => array(Type::getType(Type::BINDATAUUIDRFC4122), new \MongoDB\BSON\Binary(str_repeat('a', 16), \MongoDB\BSON\Binary::TYPE_UUID)),
            'binDataMD5' => array(Type::getType(Type::BINDATAMD5), new \MongoDB\BSON\Binary(md5('ODM'), \MongoDB\BSON\Binary::TYPE_MD5)),
            'binDataCustom' => array(Type::getType(Type::BINDATACUSTOM), new \MongoDB\BSON\Binary('foobarbaz', \MongoDB\BSON\Binary::TYPE_USER_DEFINED)),
            'objectId' => array(Type::getType(Type::OBJECTID), new \MongoDB\BSON\ObjectId()),
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
