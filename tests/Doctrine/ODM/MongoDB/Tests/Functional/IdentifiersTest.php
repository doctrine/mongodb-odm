<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional;

class IdentifiersTest extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    public function testGetIdentifierValue()
    {
        $user = new \Documents\User();
        $user->setUsername('jwage');
        $event = new \Documents\Event();
        $event->setTitle('test event title');
        $event->setUser($user);
        $this->dm->persist($user);
        $this->dm->persist($event);
        $this->dm->flush();
        $this->dm->clear();

        $test = $this->dm->getRepository(get_class($event))->find($event->getId());

        $this->assertEquals($user->getId(), $test->getUser()->getId());
        $this->assertFalse($test->getUser()->__isInitialized__);

        $this->dm->clear();

        $class = $this->dm->getClassMetadata(get_class($test->getUser()));

        $test = $this->dm->getRepository(get_class($event))->find($event->getId());
        $this->assertEquals($user->getId(), $class->getIdentifierValue($test->getUser()));
        $this->assertEquals($user->getId(), $class->getFieldValue($test->getUser(), 'id'));
        $this->assertFalse($test->getUser()->__isInitialized__);

        $this->assertEquals('jwage', $test->getUser()->getUsername());
        $this->assertTrue($test->getUser()->__isInitialized__);
    }

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

        $qb = $this->dm->createQueryBuilder('Documents\User')
            ->findAndUpdate()
            ->returnNew(true)
            ->hydrate(true)
            ->field('username')->equals('jwage')
            ->field('count')->inc(1);

        $result = $qb->refresh(false)->getQuery()->execute();
        $this->assertEquals(0, $result->getCount());

        $result = $qb->refresh(false)->getQuery()->execute();
        $this->assertEquals(0, $result->getCount());

        $result = $qb->refresh(true)->getQuery()->execute();
        $this->assertEquals(3, $result->getCount());
    }
}
