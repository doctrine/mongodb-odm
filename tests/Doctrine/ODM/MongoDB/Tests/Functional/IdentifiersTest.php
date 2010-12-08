<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional;

use Doctrine\ODM\MongoDB\Query\Query;

class IdentifiersTest extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    public function testIdentifiersAreSet()
    {
        $user = new \Documents\User();
        $user->setUsername('jwage');
        $user->setPassword('test');

        $this->dm->persist($user);
        $this->dm->flush();

        $this->assertTrue($user->getId() !== '');
    }

    public function testIdentityMap()
    {
        $user = new \Documents\User();
        $user->setUsername('jwage');

        $this->dm->persist($user);
        $this->dm->flush();

        $qb = $this->dm->createQueryBuilder('Documents\User')
            ->field('id')->equals($user->getId());

        $user = $qb->getQuery()->getSingleResult();
        $this->assertSame($user, $user);

        $this->dm->clear();

        $user2 = $qb->getQuery()->getSingleResult();
        $this->assertNotSame($user, $user2);

        $user2->setUsername('changed');

        $qb->refresh();

        $user3 = $qb->getQuery()->getSingleResult();
        $this->assertSame($user2, $user3);
        $this->assertEquals('jwage', $user3->getUsername());

        $user3->setUsername('changed');

        $qb->refresh(false);

        $user4 = $qb->getQuery()->getSingleResult();
        $this->assertEquals('changed', $user4->getUsername());

        $qb = $this->dm->createQueryBuilder('Documents\USer')
            ->findAndUpdate()
            ->returnNew(true)
            ->hydrate(true)
            ->field('username')->equals('jwage')
            ->field('count')->inc(1);

        $result = $qb->getQuery()->execute();

        $result = $qb->refresh(false)->getQuery()->execute();
        $this->assertEquals(1, $result->getCount());

        $result = $qb->refresh(true)->getQuery()->execute();
        $this->assertEquals(3, $result->getCount());
    }
}