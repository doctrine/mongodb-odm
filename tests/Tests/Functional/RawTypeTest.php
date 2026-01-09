<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional;

use Doctrine\ODM\MongoDB\Mapping\Attribute as ODM;
use Doctrine\ODM\MongoDB\Tests\BaseTestCase;
use MongoDB\BSON\ObjectId;
use MongoDB\BSON\UTCDateTime;
use PHPUnit\Framework\Attributes\DataProvider;

class RawTypeTest extends BaseTestCase
{
    #[DataProvider('getTestRawTypeData')]
    public function testRawType(mixed $value): void
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

#[ODM\Document]
class RawType
{
    /** @var string|null */
    #[ODM\Id]
    public $id;

    /** @var mixed */
    #[ODM\Field(type: 'raw')]
    public $raw;
}
