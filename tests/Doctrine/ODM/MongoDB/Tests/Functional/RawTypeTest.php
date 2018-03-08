<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Tests\BaseTest;
use MongoDB\BSON\ObjectId;
use MongoDB\BSON\UTCDateTime;
use function get_class;

class RawTypeTest extends BaseTest
{
    /**
     * @dataProvider getTestRawTypeData
     */
    public function testRawType($value)
    {
        $test = new RawType();
        $test->raw = $value;

        $this->dm->persist($test);
        $this->dm->flush();

        $result = $this->dm->getDocumentCollection(get_class($test))->findOne(['_id' => new ObjectId($test->id)]);
        $this->assertEquals($value, $result['raw']);
    }

    public function getTestRawTypeData()
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
    /** @ODM\Id */
    public $id;

    /** @ODM\Field(type="raw") */
    public $raw;
}
