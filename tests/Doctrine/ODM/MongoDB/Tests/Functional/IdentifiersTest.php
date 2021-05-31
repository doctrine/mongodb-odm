<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional;

use Doctrine\ODM\MongoDB\Tests\BaseTest;
use Documents\Event;
use Documents\User;

use function assert;
use function get_class;

class IdentifiersTest extends BaseTest
{
    public function testGetIdentifierValue()
    {
        $user = new User();
        $user->setUsername('jwage');
        $event = new Event();
        $event->setTitle('test event title');
        $event->setUser($user);
        $this->dm->persist($user);
        $this->dm->persist($event);
        $this->dm->flush();
        $this->dm->clear();

        $test = $this->dm->getRepository(get_class($event))->find($event->getId());

        $this->assertEquals($user->getId(), $test->getUser()->getId());
        $this->assertFalse($test->getUser()->isProxyInitialized());

        $this->dm->clear();

        $class = $this->dm->getClassMetadata(get_class($test->getUser()));

        $test = $this->dm->getRepository(get_class($event))->find($event->getId());
        $this->assertEquals($user->getId(), $class->getIdentifierValue($test->getUser()));
        $this->assertEquals($user->getId(), $class->getFieldValue($test->getUser(), 'id'));
        $this->assertFalse($test->getUser()->isProxyInitialized());

        $this->assertEquals('jwage', $test->getUser()->getUsername());
        $this->assertTrue($test->getUser()->isProxyInitialized());
    }

    public function testIdentifiersAreSet()
    {
        $user = new User();
        $user->setUsername('jwage');
        $user->setPassword('test');

        $this->dm->persist($user);
        $this->dm->flush();

        $this->assertNotSame('', $user->getId());
    }

    public function testIdentityMap()
    {
        $user = new User();
        $user->setUsername('jwage');

        $this->dm->persist($user);
        $this->dm->flush();

        $qb = $this->dm->createQueryBuilder(User::class)
            ->field('id')->equals($user->getId());

        $user = $qb->getQuery()->getSingleResult();
        assert($user instanceof User);
        $this->assertSame($user, $user);

        $this->dm->clear();

        $user2 = $qb->getQuery()->getSingleResult();
        assert($user2 instanceof User);
        $this->assertNotSame($user, $user2);

        $user2->setUsername('changed');

        $qb->refresh();

        $user3 = $qb->getQuery()->getSingleResult();
        assert($user3 instanceof User);
        $this->assertSame($user2, $user3);
        $this->assertEquals('jwage', $user3->getUsername());

        $user3->setUsername('changed');

        $qb->refresh(false);

        $user4 = $qb->getQuery()->getSingleResult();
        assert($user4 instanceof User);
        $this->assertEquals('changed', $user4->getUsername());

        $qb = $this->dm->createQueryBuilder(User::class)
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
