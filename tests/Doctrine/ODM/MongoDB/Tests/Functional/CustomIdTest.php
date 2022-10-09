<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional;

use Doctrine\ODM\MongoDB\Tests\BaseTest;
use Documents\Account;
use Documents\CustomUser;
use Documents\User;

class CustomIdTest extends BaseTest
{
    public function testSetId(): void
    {
        $account = new Account();
        $account->setName('Jon Test Account');

        $user = new CustomUser();
        $user->setId('userId');
        $user->setUsername('jon');
        $user->setPassword('changeme');
        $user->setAccount($account);

        self::assertEquals('userId', $user->getId());

        $this->dm->persist($user);
        $this->dm->flush();

        self::assertEquals('userId', $user->getId());

        $this->dm->clear();

        $user = $this->dm->find(CustomUser::class, $user->getId());

        self::assertNotNull($user);

        self::assertEquals('userId', $user->getId());

        $this->dm->clear();
        unset($user);

        $user = $this->dm->find(CustomUser::class, 'userId');

        self::assertNotNull($user);

        self::assertEquals('userId', $user->getId());
    }

    public function testBatchInsertCustomId(): void
    {
        $account = new Account();
        $account->setName('Jon Test Account');

        $user1 = new CustomUser();
        $user1->setId('userId');
        $user1->setUsername('user1');
        $user1->setPassword('changeme');
        $user1->setAccount($account);

        $user2 = new User();
        $user2->setUsername('user2');
        $user2->setPassword('changeme');
        $user2->setAccount($account);

        $user3 = new User();
        $user3->setUsername('user3');
        $user3->setPassword('changeme');
        $user3->setAccount($account);

        $this->dm->persist($user1);
        $this->dm->persist($user2);
        $this->dm->persist($user3);

        $this->dm->flush();
        $this->dm->clear();

        unset($user1, $user2, $user3);

        $users = $this->dm->getRepository(User::class)->findAll();

        self::assertCount(2, $users);

        $results = ['userId' => false];
        foreach ($users as $user) {
            if ($user->getId() === 'userId') {
                $results['userId'] = true;
            }

            self::assertNotNull($user->getId());
            $results['ids'][] = $user->getId();
        }

        $users = $this->dm->getRepository(CustomUser::class)->findAll();

        self::assertCount(1, $users);

        foreach ($users as $user) {
            if ($user->getId() === 'userId') {
                $results['userId'] = true;
            }

            self::assertNotNull($user->getId());
            $results['ids'][] = $user->getId();
        }

        self::assertTrue($results['userId']);
        self::assertCount(3, $results['ids']);
    }

    public function testFindUser(): void
    {
        $account = new Account();
        $account->setName('Jon Test Account');

        $user1 = new CustomUser();
        $user1->setId('userId');
        $user1->setUsername('user1');
        $user1->setPassword('changeme');
        $user1->setAccount($account);

        $user2 = new User();
        $user2->setUsername('user2');
        $user2->setPassword('changeme');
        $user2->setAccount($account);

        $user3 = new User();
        $user3->setUsername('user3');
        $user3->setPassword('changeme');
        $user3->setAccount($account);

        $this->dm->persist($user1);
        $this->dm->persist($user2);
        $this->dm->persist($user3);

        $this->dm->flush();
        $this->dm->clear();

        unset($user1, $user2, $user3);

        $user = $this->dm->find(CustomUser::class, 'userId');

        self::assertNotNull($user);
        self::assertEquals('userId', $user->getId());
        self::assertEquals('user1', $user->getUsername());

        $this->dm->clear();
        unset($user);

        self::assertNull($this->dm->find(User::class, 'userId'));
        self::assertNull($this->dm->find(CustomUser::class, 'asd'));
    }
}
