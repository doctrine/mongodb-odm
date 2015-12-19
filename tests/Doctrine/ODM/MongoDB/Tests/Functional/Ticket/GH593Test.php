<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ODM\MongoDB\DocumentNotFoundException;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

class GH593Test extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    public function setUp()
    {
        parent::setUp();

        $this->dm->getFilterCollection()->enable('testFilter');
        $filter = $this->dm->getFilterCollection()->getFilter('testFilter');
        $filter->setParameter('class', __NAMESPACE__ . '\GH593User');
        $filter->setParameter('field', 'deleted');
        $filter->setParameter('value', false);
    }

    public function testReferenceManyOwningSidePreparesFilterCriteria()
    {
        $class = __NAMESPACE__ . '\GH593User';

        $user1 = new GH593User();
        $user2 = new GH593User();
        $user3 = new GH593User();

        $user1->following->add($user2);
        $user1->following->add($user3);
        $user3->deleted = true;

        $this->dm->persist($user1);
        $this->dm->persist($user2);
        $this->dm->persist($user3);
        $this->dm->flush();
        $this->dm->clear($class);

        /* We cannot simply reinitialize the PersistentCollection, because its
         * $mongoData property has already been cleared and DocumentPersister
         * would be unable to issue a new query for related documents.
         */
        $user1 = $this->dm->find($class, $user1->getId());
        $user1following = iterator_to_array($user1->following, false);

        /* FilterCollection criteria will only be considered upon initialization
         * of the Proxy object, so expect an exception at that time. This is not
         * ideal, but it is the current behavior for hydrating the owning side
         * of a reference-many collection.
         */
        $this->assertCount(2, $user1following);

        $this->assertInstanceOf('Doctrine\ODM\MongoDB\Proxy\Proxy', $user1following[0]);
        $this->assertTrue($user1following[0]->__isInitialized());
        $this->assertEquals($user2->getId(), $user1following[0]->getId());

        $this->assertInstanceOf('Doctrine\ODM\MongoDB\Proxy\Proxy', $user1following[1]);
        $this->assertFalse($user1following[1]->__isInitialized());
        $this->assertEquals($user3->getId(), $user1following[1]->getId());

        try {
            $user1following[1]->__load();
            $this->fail('Expected DocumentNotFoundException for filtered Proxy object');
        } catch (DocumentNotFoundException $e) {
        }
    }

    public function testReferenceManyInverseSidePreparesFilterCriteria()
    {
        $class = __NAMESPACE__ . '\GH593User';

        $user1 = new GH593User();
        $user2 = new GH593User();
        $user3 = new GH593User();

        $user1->following->add($user3);
        $user2->following->add($user3);
        $user2->deleted = true;

        $this->dm->persist($user1);
        $this->dm->persist($user2);
        $this->dm->persist($user3);
        $this->dm->flush();
        $this->dm->clear($class);

        /* We cannot simply reinitialize the PersistentCollection, because its
         * $mongoData property has already been cleared and DocumentPersister
         * would be unable to issue a new query for related documents.
         */
        $user3 = $this->dm->find($class, $user3->getId());
        $user3followedBy = iterator_to_array($user3->followedBy, false);

        $this->assertCount(1, $user3followedBy);
        $this->assertEquals($user1->getId(), $user3followedBy[0]->getId());
    }
}

/** @ODM\Document */
class GH593User
{
    /** @ODM\Id */
    public $id;

    /** @ODM\Field(name="d", type="bool") */
    public $deleted = false;

    /** @ODM\ReferenceMany(targetDocument="GH593User", inversedBy="followedBy", simple=true) */
    public $following;

    /** @ODM\ReferenceMany(targetDocument="GH593User", mappedBy="following") */
    public $followedBy;

    public function __construct()
    {
        $this->following = new ArrayCollection();
        $this->followedBy = new ArrayCollection();
    }

    // Return the identifier without triggering Proxy initialization
    public function getId()
    {
        return $this->id;
    }
}
