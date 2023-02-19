<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\ODM\MongoDB\PersistentCollection\PersistentCollectionInterface;
use Doctrine\ODM\MongoDB\Tests\BaseTestCase;
use Documents\Group;
use Documents\Phonenumber;
use Documents\User;

class GH909Test extends BaseTestCase
{
    public function testManyReferenceAddAndPersist(): void
    {
        $user = new User();
        $user->addGroup(new Group('Group A'));
        $user->addGroup(new Group('Group B'));

        $this->dm->persist($user);
        $this->dm->flush();
        $this->dm->clear();

        $user = $this->dm->find(User::class, $user->getId());

        $groups = $user->getGroups();
        self::assertCount(2, $groups);
        self::assertInstanceOf(PersistentCollectionInterface::class, $groups);
        self::assertTrue($groups->isInitialized());

        $user->addGroup(new Group('Group C'));
        self::assertCount(3, $groups);
        self::assertTrue($groups->isInitialized());

        $this->dm->persist($user);
        $this->dm->flush();

        $groups->initialize();
        self::assertCount(3, $groups);
        self::assertTrue($groups->isInitialized());

        $user->addGroup(new Group('Group D'));
        self::assertCount(4, $groups);
        self::assertTrue($groups->isInitialized());

        $this->dm->persist($user);
        $this->dm->flush();

        self::assertCount(4, $groups);
        self::assertTrue($groups->isInitialized());
    }

    public function testManyEmbeddedAddAndPersist(): void
    {
        $user = new User();
        $user->addPhoneNumber(new Phonenumber('111-111-1111'));
        $user->addPhoneNumber(new Phonenumber('222-222-2222'));

        $this->dm->persist($user);
        $this->dm->flush();
        $this->dm->clear();

        $user = $this->dm->find(User::class, $user->getId());

        $phoneNumbers = $user->getPhoneNumbers();
        self::assertInstanceOf(PersistentCollectionInterface::class, $phoneNumbers);
        self::assertCount(2, $phoneNumbers);
        self::assertTrue($phoneNumbers->isInitialized());

        $user->addPhoneNumber(new Phonenumber('333-333-3333'));
        self::assertCount(3, $phoneNumbers);
        self::assertTrue($phoneNumbers->isInitialized());

        $this->dm->persist($user);
        $this->dm->flush();

        $phoneNumbers->initialize();
        self::assertCount(3, $phoneNumbers);
        self::assertTrue($phoneNumbers->isInitialized());
    }
}
