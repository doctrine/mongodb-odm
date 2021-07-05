<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Events;

use DateTime;
use Doctrine\ODM\MongoDB\Event;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Tests\BaseTest;
use Doctrine\ODM\MongoDB\UnitOfWork;

class LifecycleCallbacksTest extends BaseTest
{
    private function createUser($name = 'jon', $fullName = 'Jonathan H. Wage'): User
    {
        $user                = new User();
        $user->name          = $name;
        $user->profile       = new Profile();
        $user->profile->name = $fullName;
        $this->dm->persist($user);
        $this->dm->flush();

        return $user;
    }

    public function testPreUpdateChangingValue(): void
    {
        $user = $this->createUser();
        $this->dm->clear();

        $user = $this->dm->find(User::class, $user->id);
        $this->assertInstanceOf(DateTime::class, $user->createdAt);
        $this->assertInstanceOf(DateTime::class, $user->profile->createdAt);

        $user->name          = 'jon changed';
        $user->profile->name = 'changed';
        $this->dm->flush();
        $this->dm->clear();

        $user = $this->dm->find(User::class, $user->id);
        $this->assertInstanceOf(DateTime::class, $user->updatedAt);
        $this->assertInstanceOf(DateTime::class, $user->profile->updatedAt);
    }

    public function testPreAndPostPersist(): void
    {
        $user = $this->createUser();
        $this->assertTrue($user->prePersist);
        $this->assertTrue($user->profile->prePersist);

        $this->assertTrue($user->postPersist);
        $this->assertTrue($user->profile->postPersist);
    }

    public function testPreUpdate(): void
    {
        $user                = $this->createUser();
        $user->name          = 'jwage';
        $user->profile->name = 'Jon Doe';
        $this->dm->flush();

        $this->assertTrue($user->preUpdate);
        $this->assertTrue($user->profile->preUpdate);

        $this->assertTrue($user->postUpdate);
        $this->assertTrue($user->profile->postUpdate);
    }

    public function testPreFlush(): void
    {
        $user                = $this->createUser();
        $user->name          = 'jwage';
        $user->profile->name = 'Jon Doe';
        $this->dm->flush();

        $this->assertTrue($user->preFlush);
        $this->assertTrue($user->profile->preFlush);
    }

    public function testPreLoadAndPostLoad(): void
    {
        $user = $this->createUser();
        $this->dm->clear();

        $user = $this->dm->find(User::class, $user->id);

        $this->assertTrue($user->preLoad);
        $this->assertTrue($user->profile->preLoad);
        $this->assertTrue($user->postLoad);
        $this->assertTrue($user->profile->postLoad);
    }

    public function testPreAndPostRemove(): void
    {
        $user = $this->createUser();

        $this->assertTrue($this->uow->isInIdentityMap($user));
        $this->assertTrue($this->uow->isInIdentityMap($user->profile));

        $this->dm->remove($user);
        $this->dm->flush();

        $this->assertTrue($user->preRemove);
        $this->assertTrue($user->profile->preRemove);

        $this->assertTrue($user->postRemove);
        $this->assertTrue($user->profile->postRemove);
    }

    public function testEmbedManyEvent(): void
    {
        $user             = new User();
        $user->name       = 'jon';
        $profile          = new Profile();
        $profile->name    = 'testing cool ya';
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
        $user    = $this->dm->find(User::class, $user->id);
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

    public function testMultipleLevelsOfEmbedded(): void
    {
        $user                   = $this->createUser();
        $profile                = new Profile();
        $profile->name          = '2nd level';
        $user->profile->profile = $profile;
        $this->dm->flush();

        $this->assertEquals(UnitOfWork::STATE_MANAGED, $this->uow->getDocumentState($user->profile->profile));
        $this->assertTrue($this->uow->isInIdentityMap($user->profile->profile));

        $this->assertTrue($profile->prePersist);
        $this->assertTrue($profile->postPersist);
        $this->assertFalse($profile->preUpdate);
        $this->assertFalse($profile->postUpdate);

        $profile->name = '2nd level changed';
        $this->dm->flush();

        $this->assertTrue($profile->preUpdate);
        $this->assertTrue($profile->postUpdate);

        $this->dm->clear();
        $user          = $this->dm->find(User::class, $user->id);
        $profile       = $user->profile->profile;
        $profile->name = '2nd level changed again';

        $profile2         = new Profile();
        $profile2->name   = 'test';
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

    public function testReferences(): void
    {
        $user  = $this->createUser();
        $user2 = $this->createUser('maciej', 'Maciej Malarz');

        $user->friends[] = $user2;
        $this->dm->flush();

        $this->assertTrue($user->preFlush);
        $this->assertTrue($user->preUpdate);
        $this->assertTrue($user->postUpdate);
        $this->assertFalse($user2->preUpdate);
        $this->assertFalse($user2->postUpdate);
    }

    public function testEventsNotFiredForInverseSide(): void
    {
        $customer = new Customer();
        $cart     = new Cart();

        $this->dm->persist($customer);
        $this->dm->persist($cart);
        $this->dm->flush();

        $customer->cart = $cart;
        $cart->customer = $customer;
        $this->dm->flush();

        $this->assertFalse($customer->postUpdate);
        $this->assertTrue($cart->postUpdate);
    }
}

/** @ODM\Document */
class User extends BaseDocument
{
    /** @ODM\Id */
    public $id;

    /** @ODM\EmbedOne(targetDocument=Profile::class) */
    public $profile;

    /** @ODM\EmbedMany(targetDocument=Profile::class) */
    public $profiles = [];

    /** @ODM\ReferenceMany(targetDocument=User::class) */
    public $friends = [];
}

/** @ODM\Document */
class Cart extends BaseDocument
{
    /** @ODM\Id */
    public $id;

    /** @ODM\ReferenceOne(targetDocument=Customer::class, inversedBy="cart") */
    public $customer;
}

/** @ODM\Document */
class Customer extends BaseDocument
{
    /** @ODM\Id */
    public $id;

    /** @ODM\ReferenceOne(targetDocument=Cart::class, mappedBy="customer") */
    public $cart;
}

/** @ODM\EmbeddedDocument */
class Profile extends BaseDocument
{
    /** @ODM\EmbedOne(targetDocument=Profile::class) */
    public $profile;
}

/** @ODM\MappedSuperclass @ODM\HasLifecycleCallbacks */
abstract class BaseDocument
{
    /** @ODM\Field(type="string") */
    public $name;

    /** @ODM\Field(type="date") */
    public $createdAt;

    /** @ODM\Field(type="date") */
    public $updatedAt;

    public $prePersist  = false;
    public $postPersist = false;
    public $preUpdate   = false;
    public $postUpdate  = false;
    public $preRemove   = false;
    public $postRemove  = false;
    public $preLoad     = false;
    public $postLoad    = false;
    public $preFlush    = false;

    /** @ODM\PrePersist */
    public function prePersist(Event\LifecycleEventArgs $e): void
    {
        $this->prePersist = true;
        $this->createdAt  = new DateTime();
    }

    /** @ODM\PostPersist */
    public function postPersist(Event\LifecycleEventArgs $e): void
    {
        $this->postPersist = true;
    }

    /** @ODM\PreUpdate */
    public function preUpdate(Event\PreUpdateEventArgs $e): void
    {
        $this->preUpdate = true;
        $this->updatedAt = new DateTime();
    }

    /** @ODM\PostUpdate */
    public function postUpdate(Event\LifecycleEventArgs $e): void
    {
        $this->postUpdate = true;
    }

    /** @ODM\PreRemove */
    public function preRemove(Event\LifecycleEventArgs $e): void
    {
        $this->preRemove = true;
    }

    /** @ODM\PostRemove */
    public function postRemove(Event\LifecycleEventArgs $e): void
    {
        $this->postRemove = true;
    }

    /** @ODM\PreLoad */
    public function preLoad(Event\PreLoadEventArgs $e): void
    {
        $this->preLoad = true;
    }

    /** @ODM\PostLoad */
    public function postLoad(Event\LifecycleEventArgs $e): void
    {
        $this->postLoad = true;
    }

    /** @ODM\PreFlush */
    public function preFlush(Event\PreFlushEventArgs $e): void
    {
        $this->preFlush = true;
    }
}
