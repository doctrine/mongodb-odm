<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests;

use Documents\File;

class MongoCollectionTest extends BaseTestCase
{
    public function testGridFSEmptyResult(): void
    {
        $mongoCollection = $this->dm->getDocumentCollection(File::class);
        self::assertNull($mongoCollection->findOne(['_id' => 'definitelynotanid']));
    }
}
