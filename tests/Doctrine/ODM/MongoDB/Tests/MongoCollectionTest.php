<?php

namespace Doctrine\ODM\MongoDB\Tests;

/**
 * @author Bulat Shakirzyanov <mallluhuct@gmail.com>
 * @group collection
 */
class MongoCollectionTest extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    public function testGridFSEmptyResult()
    {
        $mongoCollection = $this->dm->getDocumentCollection('Documents\File');
        $this->assertNull($mongoCollection->findOne(array('_id' => 'definitelynotanid')));
    }
}