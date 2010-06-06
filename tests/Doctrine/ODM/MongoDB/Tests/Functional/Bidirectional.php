<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional;

require_once __DIR__ . '/../../../../../TestInit.php';

use Documents\User,
    Documents\Account;

/**
 * @author Bulat Shakirzyanov <bulat@theopenskyproject.com>
 */
class BidirectionalTest extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    public function testTrue()
    {
        $this->assertTrue(true);
    }

    public function testPersistence()
    {
        $user = new User();
        $user->setUsername('avalanche123');
        $user->setPassword('changeme');

        $account = new Account('customer');

        $user->setAccount($account);

        $this->dm->persist($user);
        $this->dm->flush();

        $this->assertEquals($account, $user->getAccount());
        $this->assertNotNull($account->getUser());
        $this->assertEquals($user, $account->getUser());

        $this->dm->clear();
        unset ($user, $account);

        $user = $this->dm->findOne('Documents\User');

        $this->assertNotNull($user->getAccount());
        $this->assertEquals($user, $user->getAccount()->getUser());
    }
}
