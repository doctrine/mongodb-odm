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
        $this->assertInstanceOf('MongoBinData', $check[$field]);
        $this->assertEquals($type, $check[$field]->type);
        $this->assertEquals($data, $check[$field]->bin);
    }

    public function provideData()
    {
        /* In driver versions before 1.2.11, the custom binary data type is
         * incorrectly returned as -128.
         *
         * See: https://jira.mongodb.org/browse/PHP-408
         */
        $expectedBinCustom = version_compare(phpversion('mongo'), '1.2.11', '<') ? -128 : \MongoBinData::CUSTOM;

        return array(
            array('bin', 'test', 0),
            array('binFunc', 'test', \MongoBinData::FUNC),
            array('binByteArray', 'test', \MongoBinData::BYTE_ARRAY),
            array('binUUID', 'test', \MongoBinData::UUID),
            array('binMD5', 'test', \MongoBinData::MD5),
            array('binCustom', 'test', $expectedBinCustom),
        );
    }
}

/** @ODM\Document */
class BinDataTestUser
{
    /** @ODM\Id */
    public $id;

    /** @ODM\Bin */
    public $bin;

    /** @ODM\Bin(type="bin_func") */
    public $binFunc;

    /** @ODM\Bin(type="bin_bytearray") */
    public $binByteArray;

    /** @ODM\Bin(type="bin_uuid") */
    public $binUUID;

    /** @ODM\Bin(type="bin_md5") */
    public $binMD5;

    /** @ODM\Bin(type="bin_custom") */
    public $binCustom;
}
