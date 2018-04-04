<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests;

use Documents\File;

class MongoCollectionTest extends BaseTest
{
    public function testGridFSEmptyResult()
    {
        $mongoCollection = $this->dm->getDocumentCollection(File::class);
        $this->assertNull($mongoCollection->findOne(['_id' => 'definitelynotanid']));
    }
}
