<?php

require_once 'TestInit.php';

use Doctrine\Common\ClassLoader,
    Doctrine\Common\Cache\ApcCache,
    Doctrine\Common\Annotations\AnnotationReader,
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

class PersistingTest extends BaseTest
{
    public function testCascadeInsertUpdateAndRemove()
    {
        $user = new User();
        $user->username = 'jon';
        $user->password = 'changeme';
        $user->account = new Account();
        $user->account->name = 'Jon Test Account';

        $this->dm->persist($user);
        $this->dm->flush();

        $user->account->name = 'w00t';
        $this->dm->flush();

        $this->dm->refresh($user);
        $this->dm->loadDocumentReference($user, 'account');
        
        $this->assertEquals('w00t', $user->account->name);

        $this->dm->remove($user);
        $this->dm->flush();
        $this->dm->clear();
    }

    public function testDetach()
    {
        $user = new User();
        $user->username = 'jon';
        $user->password = 'changeme';
        $this->dm->persist($user);
        $this->dm->flush();

        $user->username = 'whoop';
        $this->dm->detach($user);
        $this->dm->flush();
        $this->dm->clear();

        $user2 = $this->dm->findByID('Documents\User', $user->id);
        $this->assertEquals('jon', $user2->username);
    }
}