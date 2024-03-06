<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Tests\BaseTestCase;
use MongoDB\BSON\Binary;
use PHPUnit\Framework\Attributes\DataProvider;

class BinDataTest extends BaseTestCase
{
    #[DataProvider('provideData')]
    public function testBinData(string $field, string $data, int $type): void
    {
        $test         = new BinDataTestUser();
        $test->$field = $data;
        $this->dm->persist($test);
        $this->dm->flush();

        $check = $this->dm->getDocumentCollection($test::class)->findOne([]);
        self::assertInstanceOf(Binary::class, $check[$field]);
        self::assertEquals($type, $check[$field]->getType());
        self::assertEquals($data, $check[$field]->getData());
    }

    public static function provideData(): array
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

#[ODM\Document]
class BinDataTestUser
{
    /** @var string|null */
    #[ODM\Id]
    public $id;

    /** @var string|null */
    #[ODM\Field(type: 'bin')]
    public $bin;

    /** @var string|null */
    #[ODM\Field(type: 'bin_func')]
    public $binFunc;

    /** @var string|null */
    #[ODM\Field(type: 'bin_bytearray')]
    public $binByteArray;

    /** @var string|null */
    #[ODM\Field(type: 'bin_uuid')]
    public $binUUID;

    /** @var string|null */
    #[ODM\Field(type: 'bin_uuid_rfc4122')]
    public $binUUIDRFC4122;

    /** @var string|null */
    #[ODM\Field(type: 'bin_md5')]
    public $binMD5;

    /** @var string|null */
    #[ODM\Field(type: 'bin_custom')]
    public $binCustom;
}
