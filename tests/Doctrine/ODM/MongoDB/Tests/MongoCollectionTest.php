<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests;

class MongoCollectionTest extends BaseTest
{
    public function testGridFSEmptyResult()
    {
        $mongoCollection = $this->dm->getDocumentCollection('Documents\File');
        $this->assertNull($mongoCollection->findOne(['_id' => 'definitelynotanid']));
    }
}
