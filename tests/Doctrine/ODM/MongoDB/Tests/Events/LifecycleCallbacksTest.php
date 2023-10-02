<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Events;

use DateTime;
use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\Event;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Tests\BaseTestCase;
use Doctrine\ODM\MongoDB\UnitOfWork;

class LifecycleCallbacksTest extends BaseTestCase
{
    private function createUser(string $name = 'jon', string $fullName = 'Jonathan H. Wage'): User
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
        self::assertInstanceOf(DateTime::class, $user->createdAt);
        self::assertInstanceOf(DateTime::class, $user->profile->createdAt);

        $user->name          = 'jon changed';
        $user->profile->name = 'changed';
        $this->dm->flush();
        $this->dm->clear();

        $user = $this->dm->find(User::class, $user->id);
        self::assertInstanceOf(DateTime::class, $user->updatedAt);
        self::assertInstanceOf(DateTime::class, $user->profile->updatedAt);
    }

    public function testPreAndPostPersist(): void
    {
        $user = $this->createUser();
        self::assertTrue($user->prePersist);
        self::assertTrue($user->profile->prePersist);

        self::assertTrue($user->postPersist);
        self::assertTrue($user->profile->postPersist);
    }

    public function testPreUpdate(): void
    {
        $user                = $this->createUser();
        $user->name          = 'jwage';
        $user->profile->name = 'Jon Doe';
        $this->dm->flush();

        self::assertTrue($user->preUpdate);
        self::assertTrue($user->profile->preUpdate);

        self::assertTrue($user->postUpdate);
        self::assertTrue($user->profile->postUpdate);
    }

    public function testPreFlush(): void
    {
        $user                = $this->createUser();
        $user->name          = 'jwage';
        $user->profile->name = 'Jon Doe';
        $this->dm->flush();

        self::assertTrue($user->preFlush);
        self::assertTrue($user->profile->preFlush);
    }

    public function testPreLoadAndPostLoad(): void
    {
        $user = $this->createUser();
        $this->dm->clear();

        $user = $this->dm->find(User::class, $user->id);

        self::assertTrue($user->preLoad);
        self::assertTrue($user->profile->preLoad);
        self::assertTrue($user->postLoad);
        self::assertTrue($user->profile->postLoad);
    }

    public function testPreAndPostRemove(): void
    {
        $user = $this->createUser();

        self::assertTrue($this->uow->isInIdentityMap($user));
        self::assertTrue($this->uow->isInIdentityMap($user->profile));

        $this->dm->remove($user);
        $this->dm->flush();

        self::assertTrue($user->preRemove);
        self::assertTrue($user->profile->preRemove);

        self::assertTrue($user->postRemove);
        self::assertTrue($user->profile->postRemove);
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

        self::assertTrue($profile->prePersist);
        self::assertTrue($profile->postPersist);
        self::assertFalse($profile->preUpdate);
        self::assertFalse($profile->postUpdate);

        $profile->name = 'changed';
        $this->dm->flush();

        self::assertTrue($profile->preUpdate);
        self::assertTrue($profile->postUpdate);

        $this->dm->clear();
        $user    = $this->dm->find(User::class, $user->id);
        $profile = $user->profiles[0];

        self::assertTrue($profile->preLoad);
        self::assertTrue($profile->postLoad);

        $profile->name = 'w00t';
        $this->dm->flush();

        self::assertTrue($user->preUpdate);
        self::assertTrue($user->postUpdate);
        self::assertTrue($profile->preUpdate);
        self::assertTrue($profile->postUpdate);

        $this->dm->remove($user);
        $this->dm->flush();

        self::assertTrue($user->preRemove);
        self::assertTrue($user->postRemove);
        self::assertTrue($profile->preRemove);
        self::assertTrue($profile->postRemove);
    }

    public function testMultipleLevelsOfEmbedded(): void
    {
        $user                   = $this->createUser();
        $profile                = new Profile();
        $profile->name          = '2nd level';
        $user->profile->profile = $profile;
        $this->dm->flush();

        self::assertEquals(UnitOfWork::STATE_MANAGED, $this->uow->getDocumentState($user->profile->profile));
        self::assertTrue($this->uow->isInIdentityMap($user->profile->profile));

        self::assertTrue($profile->prePersist);
        self::assertTrue($profile->postPersist);
        self::assertFalse($profile->preUpdate);
        self::assertFalse($profile->postUpdate);

        $profile->name = '2nd level changed';
        $this->dm->flush();

        self::assertTrue($profile->preUpdate);
        self::assertTrue($profile->postUpdate);

        $this->dm->clear();
        $user          = $this->dm->find(User::class, $user->id);
        $profile       = $user->profile->profile;
        $profile->name = '2nd level changed again';

        $profile2         = new Profile();
        $profile2->name   = 'test';
        $user->profiles[] = $profile2;
        $this->dm->flush();

        self::assertFalse($profile->prePersist);
        self::assertFalse($profile->postPersist);
        self::assertTrue($profile->preUpdate);
        self::assertTrue($profile->postUpdate);

        self::assertTrue($profile2->prePersist);
        self::assertTrue($profile2->postPersist);
        self::assertFalse($profile2->preUpdate);
        self::assertFalse($profile2->postUpdate);

        $this->dm->remove($user);
        $this->dm->flush();

        self::assertTrue($user->preRemove);
        self::assertTrue($user->postRemove);

        self::assertTrue($user->profile->preRemove);
        self::assertTrue($user->profile->postRemove);

        self::assertTrue($user->profile->profile->preRemove);
        self::assertTrue($user->profile->profile->postRemove);

        self::assertTrue($user->profiles[0]->preRemove);
        self::assertTrue($user->profiles[0]->postRemove);
    }

    public function testReferences(): void
    {
        $user  = $this->createUser();
        $user2 = $this->createUser('maciej', 'Maciej Malarz');

        $user->friends[] = $user2;
        $this->dm->flush();

        self::assertTrue($user->preFlush);
        self::assertTrue($user->preUpdate);
        self::assertTrue($user->postUpdate);
        self::assertFalse($user2->preUpdate);
        self::assertFalse($user2->postUpdate);
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

        self::assertFalse($customer->postUpdate);
        self::assertTrue($cart->postUpdate);
    }
}

/** @ODM\Document */
class User extends BaseDocument
{
    /**
     * @ODM\Id
     *
     * @var string|null
     */
    public $id;

    /**
     * @ODM\EmbedOne(targetDocument=Profile::class)
     *
     * @var Profile|null
     */
    public $profile;

    /**
     * @ODM\EmbedMany(targetDocument=Profile::class)
     *
     * @var Collection<int, Profile>|array<Profile>
     */
    public $profiles = [];

    /**
     * @ODM\ReferenceMany(targetDocument=User::class)
     *
     * @var Collection<int, User>|array<User>
     */
    public $friends = [];
}

/** @ODM\Document */
class Cart extends BaseDocument
{
    /**
     * @ODM\Id
     *
     * @var string|null
     */
    public $id;

    /**
     * @ODM\ReferenceOne(targetDocument=Customer::class, inversedBy="cart")
     *
     * @var Customer|null
     */
    public $customer;
}

/** @ODM\Document */
class Customer extends BaseDocument
{
    /**
     * @ODM\Id
     *
     * @var string|null
     */
    public $id;

    /**
     * @ODM\ReferenceOne(targetDocument=Cart::class, mappedBy="customer")
     *
     * @var Cart|null
     */
    public $cart;
}

/** @ODM\EmbeddedDocument */
class Profile extends BaseDocument
{
    /**
     * @ODM\EmbedOne(targetDocument=Profile::class)
     *
     * @var Profile|null
     */
    public $profile;
}

/**
 * @ODM\MappedSuperclass
 * @ODM\HasLifecycleCallbacks
 */
abstract class BaseDocument
{
    /**
     * @ODM\Field(type="string")
     *
     * @var string|null
     */
    public $name;

    /**
     * @ODM\Field(type="date")
     *
     * @var DateTime
     */
    public $createdAt;

    /**
     * @ODM\Field(type="date")
     *
     * @var DateTime|null
     */
    public $updatedAt;

    /** @var bool */
    public $prePersist = false;

    /** @var bool */
    public $postPersist = false;

    /** @var bool */
    public $preUpdate = false;

    /** @var bool */
    public $postUpdate = false;

    /** @var bool */
    public $preRemove = false;

    /** @var bool */
    public $postRemove = false;

    /** @var bool */
    public $preLoad = false;

    /** @var bool */
    public $postLoad = false;

    /** @var bool */
    public $preFlush = false;

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
