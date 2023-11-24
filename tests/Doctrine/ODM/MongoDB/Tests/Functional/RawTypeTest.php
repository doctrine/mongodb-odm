<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Tests\BaseTestCase;
use MongoDB\BSON\ObjectId;
use MongoDB\BSON\UTCDateTime;
use PHPUnit\Framework\Attributes\DataProvider;

class RawTypeTest extends BaseTestCase
{
    /** @param mixed $value */
    #[DataProvider('getTestRawTypeData')]
    public function testRawType($value): void
    {
        $test      = new RawType();
        $test->raw = $value;

        $this->dm->persist($test);
        $this->dm->flush();

        $result = $this->dm->getDocumentCollection($test::class)->findOne(['_id' => new ObjectId($test->id)]);
        self::assertEquals($value, $result['raw']);
    }

    public static function getTestRawTypeData(): array
    {
        return [
            ['test'],
            [1],
            [0],
            [['test' => 'test']],
            [new UTCDateTime()],
            [true],
            [['date' => new UTCDateTime()]],
            [new ObjectId()],
        ];
    }
}

/** @ODM\Document */
class RawType
{
    /**
     * @ODM\Id
     *
     * @var string|null
     */
    public $id;

    /**
     * @ODM\Field(type="raw")
     *
     * @var mixed
     */
    public $raw;
}
