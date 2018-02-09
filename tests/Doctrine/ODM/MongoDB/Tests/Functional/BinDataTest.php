<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Tests\BaseTest;
use MongoDB\BSON\Binary;
use function get_class;

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

        $check = $this->dm->getDocumentCollection(get_class($test))->findOne([]);
        $this->assertInstanceOf(Binary::class, $check[$field]);
        $this->assertEquals($type, $check[$field]->getType());
        $this->assertEquals($data, $check[$field]->getData());
    }

    public function provideData()
    {
        return [
            ['bin', 'test', Binary::TYPE_GENERIC],
            ['binFunc', 'test', Binary::TYPE_FUNCTION],
            ['binByteArray', 'test', Binary::TYPE_OLD_BINARY],
            ['binUUID', 'testtesttesttest', Binary::TYPE_OLD_UUID],
            ['binUUIDRFC4122', '1234567890ABCDEF', Binary::TYPE_UUID],
            ['binMD5', 'test', Binary::TYPE_MD5],
            ['binCustom', 'test', Binary::TYPE_USER_DEFINED],
        ];
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
