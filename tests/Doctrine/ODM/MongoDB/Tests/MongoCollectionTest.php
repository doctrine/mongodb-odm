<?php

namespace Doctrine\ODM\MongoDB\Tests;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * @author Bulat Shakirzyanov <mallluhuct@gmail.com>
 */
class MongoCollectionTest extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    public function testGridFSEmptyResult()
    {
        $mongoCollection = $this->dm->getDocumentCollection('Documents\File');
        $this->assertNull($mongoCollection->findOne(array('_id' => 'definitelynotanid')));
    }

    public function testGetDocumentCollectionRespectsReadPreferenceInheritance(){
        $expectedReadPreference = array(
            'type' => \MongoClient::RP_SECONDARY_PREFERRED
        );

        $this->dm->getConnection()->setReadPreference(\MongoClient::RP_SECONDARY_PREFERRED);
        $mongoCollection = $this->dm->getDocumentCollection(__NAMESPACE__ . '\ReadPreferenceTestDocument');

        $connectionReadPreference = $this->dm->getConnection()->getReadPreference();
        $dbReadPreference = $mongoCollection->getDatabase()->getReadPreference();
        $collectionReadPreference = $mongoCollection->getReadPreference();

        // All read preferences have to be the same
        $this->assertEquals($expectedReadPreference, $connectionReadPreference);
        $this->assertEquals($expectedReadPreference, $dbReadPreference);
        $this->assertEquals($expectedReadPreference, $collectionReadPreference);
    }
}

/** @ODM\Document */
class ReadPreferenceTestDocument
{
    /** @ODM\Id */
    public $id;

    /** @ODM\String */
    public $test;
}