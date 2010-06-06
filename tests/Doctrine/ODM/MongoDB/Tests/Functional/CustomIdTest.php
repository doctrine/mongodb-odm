<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional;

require_once __DIR__ . '/../../../../../TestInit.php';

use Documents\Account,
    Documents\CustomUser;

/**
 * @author Bulat Shakirzyanov <bulat@theopenskyproject.com>
 */
class CustomIdTest extends \Doctrine\ODM\MongoDB\Tests\BaseTest
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
        unset ($user, $account);

        $user = $this->dm->findOne('Documents\CustomUser');

        $this->assertEquals('userId', $user->getId());

        $this->dm->clear();
        unset ($user);

        $user = $this->dm->find('Documents\CustomUser', 'userId');

        $this->assertNotNull($user);

        $this->assertEquals('userId', $user->getId());
    }
}
