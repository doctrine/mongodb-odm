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

        $account = $this->dm->find('Documents\Account', $account->getId());
        $this->assertNull($account);

        $user = $this->dm->find('Documents\User', $user->getId());
        $this->assertNull($user);
    }

    /*
    public function testUnsetFromEmbeddedCollection()
    {
        $user = new User();
        $user->setUsername('jon');
        $user->addGroup(new Group('test group 1'));
        $user->addGroup(new Group('test group 2'));
        $user->addGroup(new Group('test group 3'));
        
        $this->dm->persist($user);
        $this->dm->flush();

        $groups = $user->getGroups();
        unset($groups[0]);

        $this->dm->flush();
        $this->dm->clear();

        $user = $this->dm->findOne('Documents\User', array('username' => 'jon'));

        $this->assertEquals(2, count($user->getGroups()));
    }
    */
}