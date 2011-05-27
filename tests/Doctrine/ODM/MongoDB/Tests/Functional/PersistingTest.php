<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional;

use Doctrine\Common\ClassLoader,
    Doctrine\Common\Cache\ApcCache,
    Doctrine\ODM\MongoDB\DocumentManager,
    Doctrine\ODM\MongoDB\Configuration,
    Doctrine\ODM\MongoDB\Mapping\ClassMetadata,
    Doctrine\ODM\MongoDB\Mongo,
    Doctrine\ODM\MongoDB\Mapping\Driver\AnnotationDriver,
    Documents\Account,
    Documents\Address,
    Documents\Group,
    Documents\Phonenumber,
    Documents\Profile,
    Documents\File,
    Documents\User;

class PersistingTest extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    public function testCascadeInsertUpdateAndRemove()
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

        $this->assertEquals('w00t', $user->getAccount()->getName());

        $this->dm->remove($user);
        $this->dm->flush();
        $this->dm->clear();
    }

    public function testUpdate()
    {
        $user = new User();
        $user->setInheritedProperty('cool');
        $user->setUsername('jon');
        $user->setPassword('changeme');

        $this->dm->persist($user);
        $this->dm->flush();
        $this->dm->clear();

        $user = $this->dm->find('Documents\User', $user->getId());
        
        $this->assertNotNull($user);
        $user->setUsername('w00t');
        $this->dm->flush();
        $this->dm->clear();

        $user = $this->dm->find('Documents\User', $user->getId());
        $this->assertNotNull($user);
        $this->assertEquals('w00t', $user->getUsername());
        $this->assertEquals('cool', $user->getInheritedProperty());
    }

    public function testDetach()
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

        $user2 = $this->dm->find('Documents\User', $user->getId());
        $this->assertNotNull($user2);
        $this->assertEquals('jon', $user2->getUsername());
    }
}