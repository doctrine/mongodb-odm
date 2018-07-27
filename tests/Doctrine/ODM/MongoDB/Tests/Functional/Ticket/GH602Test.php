<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ODM\MongoDB\DocumentNotFoundException;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Proxy\Proxy;
use Doctrine\ODM\MongoDB\Tests\BaseTest;
use function iterator_to_array;

class GH602Test extends BaseTest
{
    public function testReferenceManyOwningSidePreparesFilterCriteriaForDifferentClass()
    {
        $thingClass = GH602Thing::class;
        $userClass = GH602User::class;
        $this->enableDeletedFilter($thingClass);

        $user1 = new GH602User();
        $thing1 = new GH602Thing();
        $thing2 = new GH602Thing();

        $user1->likes->add($thing1);
        $user1->likes->add($thing2);
        $thing2->deleted = true;

        $this->dm->persist($user1);
        $this->dm->persist($thing1);
        $this->dm->persist($thing2);
        $this->dm->flush();
        $this->dm->clear();

        /* We cannot simply reinitialize the PersistentCollection, because its
         * $mongoData property has already been cleared and DocumentPersister
         * would be unable to issue a new query for related documents.
         */
        $user1 = $this->dm->find($userClass, $user1->getId());
        $user1likes = iterator_to_array($user1->likes, false);

        /* FilterCollection criteria will only be considered upon initialization
         * of the Proxy object, so expect an exception at that time. This is not
         * ideal, but it is the current behavior for hydrating the owning side
         * of a reference-many collection.
         */
        $this->assertCount(2, $user1likes);

        $this->assertInstanceOf(Proxy::class, $user1likes[0]);
        $this->assertTrue($user1likes[0]->__isInitialized());
        $this->assertEquals($thing1->getId(), $user1likes[0]->getId());

        $this->assertInstanceOf(Proxy::class, $user1likes[1]);
        $this->assertFalse($user1likes[1]->__isInitialized());
        $this->assertEquals($thing2->getId(), $user1likes[1]->getId());

        $this->expectException(DocumentNotFoundException::class);
        $user1likes[1]->__load();
    }

    public function testReferenceManyInverseSidePreparesFilterCriteriaForDifferentClass()
    {
        $thingClass = GH602Thing::class;
        $userClass = GH602User::class;
        $this->enableDeletedFilter($userClass);

        $user1 = new GH602User();
        $user2 = new GH602User();
        $thing1 = new GH602Thing();

        $user1->likes->add($thing1);
        $user2->likes->add($thing1);
        $user2->deleted = true;

        $this->dm->persist($user1);
        $this->dm->persist($user2);
        $this->dm->persist($thing1);
        $this->dm->flush();
        $this->dm->clear();

        /* We cannot simply reinitialize the PersistentCollection, because its
         * $mongoData property has already been cleared and DocumentPersister
         * would be unable to issue a new query for related documents.
         */
        $thing1 = $this->dm->find($thingClass, $thing1->getId());
        $thing1likedBy = iterator_to_array($thing1->likedBy, false);

        $this->assertCount(1, $thing1likedBy);
        $this->assertEquals($user1->getId(), $thing1likedBy[0]->getId());
    }

    private function enableDeletedFilter($class)
    {
        $this->dm->getFilterCollection()->enable('testFilter');
        $filter = $this->dm->getFilterCollection()->getFilter('testFilter');
        $filter->setParameter('class', $class);
        $filter->setParameter('field', 'deleted');
        $filter->setParameter('value', false);
    }
}

/** @ODM\Document */
class GH602User
{
    /** @ODM\Id */
    public $id;

    /** @ODM\Field(name="user_deleted", type="bool") */
    public $deleted = false;

    /** @ODM\ReferenceMany(targetDocument=GH602Thing::class, inversedBy="likedBy", storeAs="id") */
    public $likes;

    public function __construct()
    {
        $this->likes = new ArrayCollection();
    }

    /** Return the identifier without triggering Proxy initialization */
    public function getId()
    {
        return $this->id;
    }
}

/** @ODM\Document */
class GH602Thing
{
    /** @ODM\Id */
    public $id;

    /** @ODM\Field(name="thing_deleted", type="bool") */
    public $deleted = false;

    /** @ODM\ReferenceMany(targetDocument=GH602User::class, mappedBy="likes") */
    public $likedBy;

    public function __construct()
    {
        $this->likedBy = new ArrayCollection();
    }

    /** Return the identifier without triggering Proxy initialization */
    public function getId()
    {
        return $this->id;
    }
}
