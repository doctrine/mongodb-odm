<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional;

use Doctrine\ODM\MongoDB\Tests\BaseTest;
use Documents\Account;
use Documents\CustomUser;
use Documents\User;

class CustomIdTest extends BaseTest
{
    public function testSetId()
    {
        $account = new Account();
        $account->setName('Jon Test Account');

        $user = new CustomUser();
        $user->setId('userId');
        $user->setUsername('jon');
        $user->setPassword('changeme');
        $user->setAccount($account);

        $this->assertEquals('userId', $user->getId());

        $this->dm->persist($user);
        $this->dm->flush();

        $this->assertEquals('userId', $user->getId());

        $this->dm->clear();

        $user = $this->dm->find(CustomUser::class, $user->getId());

        $this->assertNotNull($user);

        $this->assertEquals('userId', $user->getId());

        $this->dm->clear();
        unset($user);

        $user = $this->dm->find(CustomUser::class, 'userId');

        $this->assertNotNull($user);

        $this->assertEquals('userId', $user->getId());
    }

    public function testBatchInsertCustomId()
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

        $this->assertCount(2, $users);

        $results = [];
        foreach ($users as $user) {
            if ($user->getId() === 'userId') {
                $results['userId'] = true;
            }
            $this->assertNotNull($user->getId());
            $results['ids'][] = $user->getId();
        }

        $users = $this->dm->getRepository(CustomUser::class)->findAll();

        $this->assertCount(1, $users);

        foreach ($users as $user) {
            if ($user->getId() === 'userId') {
                $results['userId'] = true;
            }
            $this->assertNotNull($user->getId());
            $results['ids'][] = $user->getId();
        }

        $this->assertTrue($results['userId']);
        $this->assertCount(3, $results['ids']);
    }

    public function testFindUser()
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

        $this->assertNotNull($user);
        $this->assertEquals('userId', $user->getId());
        $this->assertEquals('user1', $user->getUsername());

        $this->dm->clear();
        unset($user);

        $this->assertNull($this->dm->find(User::class, 'userId'));
        $this->assertNull($this->dm->find(CustomUser::class, 'asd'));
    }
}
