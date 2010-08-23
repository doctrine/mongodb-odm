<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional;

require_once __DIR__ . '/../../../../../TestInit.php';

use Documents\User;

class FindAndModifyTest extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    public function testFindAndModify()
    {
        $coll = $this->dm->getDocumentCollection('Documents\User');
        $docs = array(array('count' => 0), array('count' => 0));
        $coll->batchInsert($docs);

        // test update findAndModify
        $q = $this->dm->createQuery()
            ->update('Documents\User')
            ->findAndModify(array('new' => true))
            ->field('count')->inc(5)
            ->field('username')->set('jwage');
        $result = $q->execute();

        // Test the username was set and count incremented
        $this->assertEquals('jwage', $result->getUsername());
        $this->assertEquals(5, $result->getCount());

        // Test remove findAndModify
        $q = $this->dm->createQuery()
            ->remove('Documents\User')
            ->findAndModify()
            ->field('username')->equals('jwage');
        $result = $q->execute();

        // Test the object was returned
        $this->assertEquals('jwage', $result->getUsername());

        // Test the object was removed
        $this->assertEquals(1, $this->dm->getDocumentCollection('Documents\User')->find()->count());
    }
}