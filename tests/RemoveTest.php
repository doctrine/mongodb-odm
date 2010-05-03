<?php

require_once 'TestInit.php';

use Documents\Account,
    Documents\Address,
    Documents\Group,
    Documents\Phonenumber,
    Documents\Profile,
    Documents\File,
    Documents\User;

class RemoveTest extends BaseTest
{
    public function testRemove()
    {
        $account = new Account();
        $account->setName('Jon Test Account');

        $user = new User();
        $user->setUsername('jon');
        $user->setPassword('changeme');
        $user->setAccount($account);

        $this->dm->persist($user);
        $this->dm->flush();

        $this->dm->remove($user);
        $this->dm->flush();

        $account = $this->dm->findByID('Documents\Account', $account->getId());
        $this->assertNull($account);

        $user = $this->dm->findByID('Documents\User', $user->getId());
        $this->assertNull($user);
    }
}