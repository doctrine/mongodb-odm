<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional;

use Doctrine\ODM\MongoDB\Tests\BaseTest;
use Documents\Account;
use Documents\User;

class PersistingTest extends BaseTest
{
    public function testCascadeInsertUpdateAndRemove(): void
    {
        $account = new Account();
        $account->setName('Jon Test Account');

        $user = new User();
        $user->setUsername('jon');
        $user->setPassword('changeme');
        $user->setAccount($account);

        $this->dm->persist($user);
        $this->dm->flush();

        $account->setName('w00t');
        $this->dm->flush();

        self::assertEquals('w00t', $user->getAccount()->getName());

        $this->dm->remove($user);
        $this->dm->flush();
        $this->dm->clear();
    }

    public function testUpdate(): void
    {
        $user = new User();
        $user->setInheritedProperty('cool');
        $user->setUsername('jon');
        $user->setPassword('changeme');

        $this->dm->persist($user);
        $this->dm->flush();
        $this->dm->clear();

        $user = $this->dm->find(User::class, $user->getId());

        self::assertNotNull($user);
        $user->setUsername('w00t');
        $this->dm->flush();
        $this->dm->clear();

        $user = $this->dm->find(User::class, $user->getId());
        self::assertNotNull($user);
        self::assertEquals('w00t', $user->getUsername());
        self::assertEquals('cool', $user->getInheritedProperty());
    }

    public function testDetach(): void
    {
        $user = new User();
        $user->setUsername('jon');
        $user->setPassword('changeme');
        $this->dm->persist($user);
        $this->dm->flush();

        $user->setUsername('whoop');
        $this->dm->detach($user);
        $this->dm->flush();
        $this->dm->clear();

        $user2 = $this->dm->find(User::class, $user->getId());
        self::assertNotNull($user2);
        self::assertEquals('jon', $user2->getUsername());
    }
}
