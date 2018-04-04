<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\ODM\MongoDB\Tests\BaseTest;
use Documents\Group;
use Documents\Phonenumber;
use Documents\User;

class GH909Test extends BaseTest
{
    public function testManyReferenceAddAndPersist()
    {
        $user = new User();
        $user->addGroup(new Group('Group A'));
        $user->addGroup(new Group('Group B'));

        $this->dm->persist($user);
        $this->dm->flush();
        $this->dm->clear();

        $user = $this->dm->find(User::class, $user->getId());

        $groups = $user->getGroups();
        $this->assertCount(2, $groups);
        $this->assertTrue($groups->isInitialized());

        $user->addGroup(new Group('Group C'));
        $this->assertCount(3, $groups);
        $this->assertTrue($groups->isInitialized());

        $this->dm->persist($user);
        $this->dm->flush();

        $groups->initialize();
        $this->assertCount(3, $groups);
        $this->assertTrue($groups->isInitialized());

        $user->addGroup(new Group('Group D'));
        $this->assertCount(4, $groups);
        $this->assertTrue($groups->isInitialized());

        $this->dm->persist($user);
        $this->dm->flush();

        $this->assertCount(4, $groups);
        $this->assertTrue($groups->isInitialized());
    }

    public function testManyEmbeddedAddAndPersist()
    {
        $user = new User();
        $user->addPhoneNumber(new Phonenumber('111-111-1111'));
        $user->addPhoneNumber(new Phonenumber('222-222-2222'));

        $this->dm->persist($user);
        $this->dm->flush();
        $this->dm->clear();

        $user = $this->dm->find(User::class, $user->getId());

        $phoneNumbers = $user->getPhoneNumbers();
        $this->assertCount(2, $phoneNumbers);
        $this->assertTrue($phoneNumbers->isInitialized());

        $user->addPhoneNumber(new Phonenumber('333-333-3333'));
        $this->assertCount(3, $phoneNumbers);
        $this->assertTrue($phoneNumbers->isInitialized());

        $this->dm->persist($user);
        $this->dm->flush();

        $phoneNumbers->initialize();
        $this->assertCount(3, $phoneNumbers);
        $this->assertTrue($phoneNumbers->isInitialized());
    }
}
