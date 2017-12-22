<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Tests\BaseTest;

class BinDataTest extends BaseTest
{
    /**
     * @dataProvider provideData
     */
    public function testBinData($field, $data, $type)
    {
        $test = new BinDataTestUser();
        $test->$field = $data;
        $this->dm->persist($test);
        $this->dm->flush();

        $check = $this->dm->getDocumentCollection(get_class($test))->findOne(array());
        $this->assertInstanceOf(\MongoDB\BSON\Binary::class, $check[$field]);
        $this->assertEquals($type, $check[$field]->getType());
        $this->assertEquals($data, $check[$field]->getData());
    }

    public function provideData()
    {
        return array(
            array('bin', 'test', \MongoDB\BSON\Binary::TYPE_GENERIC),
            array('binFunc', 'test', \MongoDB\BSON\Binary::TYPE_FUNCTION),
            array('binByteArray', 'test', \MongoDB\BSON\Binary::TYPE_OLD_BINARY),
            array('binUUID', 'testtesttesttest', \MongoDB\BSON\Binary::TYPE_OLD_UUID),
            array('binUUIDRFC4122', '1234567890ABCDEF', \MongoDB\BSON\Binary::TYPE_UUID),
            array('binMD5', 'test', \MongoDB\BSON\Binary::TYPE_MD5),
            array('binCustom', 'test', \MongoDB\BSON\Binary::TYPE_USER_DEFINED),
        );
    }
}

/** @ODM\Document */
class BinDataTestUser
{
    /** @ODM\Id */
    public $id;

    /** @ODM\Field(type="bin") */
    public $bin;

    /** @ODM\Field(type="bin_func") */
    public $binFunc;

    /** @ODM\Field(type="bin_bytearray") */
    public $binByteArray;

    /** @ODM\Field(type="bin_uuid") */
    public $binUUID;

    /** @ODM\Field(type="bin_uuid_rfc4122") */
    public $binUUIDRFC4122;

    /** @ODM\Field(type="bin_md5") */
    public $binMD5;

    /** @ODM\Field(type="bin_custom") */
    public $binCustom;
}
