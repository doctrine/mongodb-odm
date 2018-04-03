<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional;

use Doctrine\ODM\MongoDB\Tests\BaseTest;
use Documents\User;

class FindAndModifyTest extends BaseTest
{
    public function testFindAndModify()
    {
        $coll = $this->dm->getDocumentCollection(User::class);
        $docs = [['count' => 0], ['count' => 0]];
        $coll->insertMany($docs);

        // test update findAndModify
        $q = $this->dm->createQueryBuilder()
            ->findAndUpdate(User::class)
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
            ->findAndRemove(User::class)
            ->field('username')->equals('jwage')
            ->getQuery();
        $result = $q->execute();

        // Test the object was returned
        $this->assertEquals('jwage', $result->getUsername());

        // Test the object was removed
        $this->assertEquals(1, $this->dm->getDocumentCollection(User::class)->count());
    }

    public function testFindAndModifyAlt()
    {
        $doc = new User();
        $doc->setUsername('jwage');

        $this->dm->persist($doc);
        $this->dm->flush();

        // test update findAndModify
        $q = $this->dm->createQueryBuilder()
            ->findAndUpdate(User::class)
            ->returnNew(true)
            ->field('username')->equals('jwage')
            ->field('username')->set('Romain Neutron')
            ->getQuery();
        $result = $q->execute();

        // Test the username was set
        $this->assertEquals('Romain Neutron', $result->getUsername());
    }
}
