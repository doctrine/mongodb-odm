<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional;

use Doctrine\ODM\MongoDB\Tests\BaseTestCase;
use Documents\Event;
use Documents\User;

use function assert;
use function get_class;

class IdentifiersTest extends BaseTestCase
{
    public function testGetIdentifierValue(): void
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

        $test = $this->dm->getRepository($event::class)->find($event->getId());

        $userTest = $test->getUser();
        self::assertEquals($user->getId(), $userTest->getId());
        self::assertTrue(self::isLazyObject($userTest));
        self::assertTrue($this->uow->isUninitializedObject($userTest));

        $this->dm->clear();

        $class = $this->dm->getClassMetadata(get_class($test->getUser()));

        $test = $this->dm->getRepository($event::class)->find($event->getId());

        $foundUser = $test->getUser();
        self::assertEquals($user->getId(), $class->getIdentifierValue($user));
        self::assertEquals($user->getId(), $class->getFieldValue($foundUser, 'id'));
        self::assertTrue(self::isLazyObject($foundUser));
        self::assertTrue($this->uow->isUninitializedObject($foundUser));

        self::assertEquals('jwage', $foundUser->getUsername());
        self::assertFalse($this->uow->isUninitializedObject($foundUser));
    }

    public function testIdentifiersAreSet(): void
    {
        $user = new User();
        $user->setUsername('jwage');
        $user->setPassword('test');

        $this->dm->persist($user);
        $this->dm->flush();

        self::assertNotSame('', $user->getId());
    }

    public function testIdentityMap(): void
    {
        $user = new User();
        $user->setUsername('jwage');

        $this->dm->persist($user);
        $this->dm->flush();

        $qb = $this->dm->createQueryBuilder(User::class)
            ->field('id')->equals($user->getId());

        $user = $qb->getQuery()->getSingleResult();
        assert($user instanceof User);
        self::assertSame($user, $user);

        $this->dm->clear();

        $user2 = $qb->getQuery()->getSingleResult();
        assert($user2 instanceof User);
        self::assertNotSame($user, $user2);

        $user2->setUsername('changed');

        $qb->refresh();

        $user3 = $qb->getQuery()->getSingleResult();
        assert($user3 instanceof User);
        self::assertSame($user2, $user3);
        self::assertEquals('jwage', $user3->getUsername());

        $user3->setUsername('changed');

        $qb->refresh(false);

        $user4 = $qb->getQuery()->getSingleResult();
        assert($user4 instanceof User);
        self::assertEquals('changed', $user4->getUsername());

        $qb = $this->dm->createQueryBuilder(User::class)
            ->findAndUpdate()
            ->returnNew(true)
            ->hydrate(true)
            ->field('username')->equals('jwage')
            ->field('count')->inc(1);

        $result = $qb->refresh(false)->getQuery()->execute();
        self::assertInstanceOf(User::class, $result);
        self::assertEquals(0, $result->getCount());

        $result = $qb->refresh(false)->getQuery()->execute();
        self::assertInstanceOf(User::class, $result);
        self::assertEquals(0, $result->getCount());

        $result = $qb->refresh(true)->getQuery()->execute();
        self::assertInstanceOf(User::class, $result);
        self::assertEquals(3, $result->getCount());
    }
}
