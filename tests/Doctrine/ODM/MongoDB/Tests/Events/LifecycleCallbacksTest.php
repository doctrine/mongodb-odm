<?php

namespace Doctrine\ODM\MongoDB\Tests\Events;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

class LifecycleCallbacksTest extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    private function createUser()
    {
        $user = new User();
        $user->name = 'jon';
        $user->profile = new Profile();
        $user->profile->name = 'Jonathan H. Wage';
        $this->dm->persist($user);
        $this->dm->flush();
        return $user;
    }

    public function testPreUpdateChangingValue()
    {
        $user = $this->createUser();
        $this->dm->clear();

        $user = $this->dm->find(__NAMESPACE__.'\User', $user->id);
        $this->assertInstanceOf('DateTime', $user->createdAt);
        $this->assertInstanceOf('DateTime', $user->profile->createdAt);

        $user->name = 'jon changed';
        $user->profile->name = 'changed';
        $this->dm->flush();
        $this->dm->clear();

        $user = $this->dm->find(__NAMESPACE__.'\User', $user->id);
        $this->assertInstanceOf('DateTime', $user->updatedAt);
        $this->assertInstanceOf('DateTime', $user->profile->updatedAt);
    }

    public function testPreAndPostPersist()
    {
        $user = $this->createUser();
        $this->assertTrue($user->prePersist);
        $this->assertTrue($user->profile->prePersist);

        $this->assertTrue($user->postPersist);
        $this->assertTrue($user->profile->postPersist);
    }

    public function testPreUpdate()
    {
        $user = $this->createUser();
        $user->name = 'jwage';
        $user->profile->name = 'Jon Doe';
        $this->dm->flush();

        $this->assertTrue($user->preUpdate);
        $this->assertTrue($user->profile->preUpdate);

        $this->assertTrue($user->postUpdate);
        $this->assertTrue($user->profile->postUpdate);
    }
    
    public function testPreFlush()
    {
        $user = $this->createUser();
        $user->name = 'jwage';
        $user->profile->name = 'Jon Doe';
        $this->dm->flush();

        $this->assertTrue($user->preFlush);
        $this->assertTrue($user->profile->preFlush);
    }

    public function testPreLoadAndPostLoad()
    {
        $user = $this->createUser();
        $this->dm->clear();

        $user = $this->dm->find(__NAMESPACE__.'\User', $user->id);

        $this->assertTrue($user->preLoad);
        $this->assertTrue($user->profile->preLoad);
        $this->assertTrue($user->postLoad);
        $this->assertTrue($user->profile->postLoad);
    }

    public function testPreAndPostRemove()
    {
        $user = $this->createUser();
        $this->dm->remove($user);
        $this->dm->flush();

        $this->assertTrue($user->preRemove);
        $this->assertTrue($user->profile->preRemove);

        $this->assertTrue($user->postRemove);
        $this->assertTrue($user->profile->postRemove);
    }

    public function testEmbedManyEvent()
    {
        $user = new User();
        $user->name = 'jon';
        $profile = new Profile();
        $profile->name = 'testing cool ya';
        $user->profiles[] = $profile;

        $this->dm->persist($user);
        $this->dm->flush();

        $this->assertTrue($profile->prePersist);
        $this->assertTrue($profile->postPersist);
        $this->assertFalse($profile->preUpdate);
        $this->assertFalse($profile->postUpdate);

        $profile->name = 'changed';
        $this->dm->flush();

        $this->assertTrue($profile->preUpdate);
        $this->assertTrue($profile->postUpdate);

        $this->dm->clear();
        $user = $this->dm->find(__NAMESPACE__.'\User', $user->id);
        $profile = $user->profiles[0];

        $this->assertTrue($profile->preLoad);
        $this->assertTrue($profile->postLoad);

        $profile->name = 'w00t';
        $this->dm->flush();

        $this->assertTrue($user->preUpdate);
        $this->assertTrue($user->postUpdate);
        $this->assertTrue($profile->preUpdate);
        $this->assertTrue($profile->postUpdate);

        $this->dm->remove($user);
        $this->dm->flush();

        $this->assertTrue($user->preRemove);
        $this->assertTrue($user->postRemove);
        $this->assertTrue($profile->preRemove);
        $this->assertTrue($profile->postRemove);
    }

    public function testMultipleLevelsOfEmbedded()
    {
        $user = $this->createUser();
        $profile = new Profile();
        $profile->name = '2nd level';
        $user->profile->profile = $profile;
        $this->dm->flush();

        $this->assertTrue($profile->prePersist);
        $this->assertTrue($profile->postPersist);
        $this->assertFalse($profile->preUpdate);
        $this->assertFalse($profile->postUpdate);

        $profile->name = '2nd level changed';
        $this->dm->flush();

        $this->assertTrue($profile->preUpdate);
        $this->assertTrue($profile->postUpdate);

        $this->dm->clear();
        $user = $this->dm->find(__NAMESPACE__.'\User', $user->id);
        $profile = $user->profile->profile;
        $profile->name = '2nd level changed again';
        
        $profile2 = new Profile();
        $profile2->name = 'test';
        $user->profiles[] = $profile2;
        $this->dm->flush();

        $this->assertFalse($profile->prePersist);
        $this->assertFalse($profile->postPersist);
        $this->assertTrue($profile->preUpdate);
        $this->assertTrue($profile->postUpdate);

        $this->assertTrue($profile2->prePersist);
        $this->assertTrue($profile2->postPersist);
        $this->assertFalse($profile2->preUpdate);
        $this->assertFalse($profile2->postUpdate);

        $this->dm->remove($user);
        $this->dm->flush();

        $this->assertTrue($user->preRemove);
        $this->assertTrue($user->postRemove);

        $this->assertTrue($user->profile->preRemove);
        $this->assertTrue($user->profile->postRemove);

        $this->assertTrue($user->profile->profile->preRemove);
        $this->assertTrue($user->profile->profile->postRemove);

        $this->assertTrue($user->profiles[0]->preRemove);
        $this->assertTrue($user->profiles[0]->postRemove);
    }
}

/** @ODM\Document */
class User extends BaseDocument
{
    /** @ODM\Id */
    public $id;

    /** @ODM\EmbedOne(targetDocument="Profile") */
    public $profile;

    /** @ODM\EmbedMany(targetDocument="Profile") */
    public $profiles = array();
}

/** @ODM\EmbeddedDocument */
class Profile extends BaseDocument
{
    /** @ODM\EmbedOne(targetDocument="Profile") */
    public $profile;
}

/** @ODM\MappedSuperclass */
abstract class BaseDocument
{
    /** @ODM\String */
    public $name;

    /** @ODM\Date */
    public $createdAt;

    /** @ODM\Date */
    public $updatedAt;

    public $prePersist = false;
    public $postPersist = false;
    public $preUpdate = false;
    public $postUpdate = false;
    public $preRemove = false;
    public $postRemove = false;
    public $preLoad = false;
    public $postLoad = false;
    public $preFlush = false;

    /** @ODM\PrePersist */
    public function prePersist()
    {
        $this->prePersist = true;
        $this->createdAt = new \DateTime();
    }

    /** @ODM\PostPersist */
    public function postPersist()
    {
        $this->postPersist = true;
    }

    /** @ODM\PreUpdate */
    public function preUpdate()
    {
        $this->preUpdate = true;
        $this->updatedAt = new \DateTime();
    }

    /** @ODM\PostUpdate */
    public function postUpdate()
    {
        $this->postUpdate = true;
    }

    /** @ODM\PreRemove */
    public function preRemove()
    {
        $this->preRemove = true;
    }

    /** @ODM\PostRemove */
    public function postRemove()
    {
        $this->postRemove = true;
    }

    /** @ODM\PreLoad */
    public function preLoad()
    {
        $this->preLoad = true;
    }

    /** @ODM\PostLoad */
    public function postLoad()
    {
        $this->postLoad = true;
    }
    
    /** @ODM\PreFlush */
    public function preFlush()
    {
        $this->preFlush = true;
    }
}