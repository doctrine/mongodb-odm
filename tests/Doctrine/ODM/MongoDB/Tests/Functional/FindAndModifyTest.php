<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional;

use Documents\User;

class FindAndModifyTest extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    public function testFindAndModify()
    {
        $coll = $this->dm->getDocumentCollection('Documents\User');
        $docs = array(array('count' => 0), array('count' => 0));
        $coll->batchInsert($docs);

        // test update findAndModify
        $q = $this->dm->createQueryBuilder()
            ->findAndUpdate('Documents\User')
            ->returnNew(true)
            ->field('count')->inc(5)
            ->field('username')->set('jwage')
            ->getQuery();
        $result = $q->execute();

        // Test the username was set and count incremented
        $this->assertEquals('jwage', $result->getUsername());
        $this->assertEquals(5, $result->getCount());

        // Test remove findAndModify
        $q = $this->dm->createQueryBuilder()
            ->findAndRemove('Documents\User')
            ->field('username')->equals('jwage')
            ->getQuery();
        $result = $q->execute();

        // Test the object was returned
        $this->assertEquals('jwage', $result->getUsername());

        // Test the object was removed
        $this->assertEquals(1, $this->dm->getDocumentCollection('Documents\User')->find()->count());
    }

    public function testFindAndModifyAlt()
    {
        $doc = new User();
        $doc->setUsername('jwage');

        $this->dm->persist($doc);
        $this->dm->flush();

        // test update findAndModify
        $q = $this->dm->createQueryBuilder()
            ->findAndUpdate('Documents\User')
            ->returnNew(true)
            ->field('username')->equals('jwage')
            ->field('username')->set('Romain Neutron')
            ->getQuery();
        $result = $q->execute();

        // Test the username was set
        $this->assertEquals('Romain Neutron', $result->getUsername());
    }
}