<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Tests\BaseTest;
use MongoDB\BSON\Binary;

use function get_class;

class BinDataTest extends BaseTest
{
    /** @dataProvider provideData */
    public function testBinData(string $field, string $data, int $type): void
    {
        $test         = new BinDataTestUser();
        $test->$field = $data;
        $this->dm->persist($test);
        $this->dm->flush();

        $check = $this->dm->getDocumentCollection(get_class($test))->findOne([]);
        self::assertInstanceOf(Binary::class, $check[$field]);
        self::assertEquals($type, $check[$field]->getType());
        self::assertEquals($data, $check[$field]->getData());
    }

    public function provideData(): array
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
    /**
     * @ODM\Id
     *
     * @var string|null
     */
    public $id;

    /**
     * @ODM\Field(type="bin")
     *
     * @var string|null
     */
    public $bin;

    /**
     * @ODM\Field(type="bin_func")
     *
     * @var string|null
     */
    public $binFunc;

    /**
     * @ODM\Field(type="bin_bytearray")
     *
     * @var string|null
     */
    public $binByteArray;

    /**
     * @ODM\Field(type="bin_uuid")
     *
     * @var string|null
     */
    public $binUUID;

    /**
     * @ODM\Field(type="bin_uuid_rfc4122")
     *
     * @var string|null
     */
    public $binUUIDRFC4122;

    /**
     * @ODM\Field(type="bin_md5")
     *
     * @var string|null
     */
    public $binMD5;

    /**
     * @ODM\Field(type="bin_custom")
     *
     * @var string|null
     */
    public $binCustom;
}
