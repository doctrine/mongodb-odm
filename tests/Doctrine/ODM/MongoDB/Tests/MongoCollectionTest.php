<?php

namespace Doctrine\ODM\MongoDB\Tests;

class MongoCollectionTest extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    public function testGridFSEmptyResult()
    {
        $mongoCollection = $this->dm->getDocumentCollection('Documents\File');
        $this->assertNull($mongoCollection->findOne(array('_id' => 'definitelynotanid')));
    }
}
