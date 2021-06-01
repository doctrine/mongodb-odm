<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\DocumentNotFoundException;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Tests\BaseTest;
use ProxyManager\Proxy\GhostObjectInterface;

use function iterator_to_array;

class GH593Test extends BaseTest
{
    public function setUp(): void
    {
        parent::setUp();

        $this->dm->getFilterCollection()->enable('testFilter');
        $filter = $this->dm->getFilterCollection()->getFilter('testFilter');
        $filter->setParameter('class', GH593User::class);
        $filter->setParameter('field', 'deleted');
        $filter->setParameter('value', false);
    }

    public function testReferenceManyOwningSidePreparesFilterCriteria()
    {
        $class = GH593User::class;

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
        $user1          = $this->dm->find($class, $user1->getId());
        $user1following = iterator_to_array($user1->following, false);

        /* FilterCollection criteria will only be considered upon initialization
         * of the Proxy object, so expect an exception at that time. This is not
         * ideal, but it is the current behavior for hydrating the owning side
         * of a reference-many collection.
         */
        $this->assertCount(2, $user1following);

        $this->assertInstanceOf(GhostObjectInterface::class, $user1following[0]);
        $this->assertTrue($user1following[0]->isProxyInitialized());
        $this->assertEquals($user2->getId(), $user1following[0]->getId());

        $this->assertInstanceOf(GhostObjectInterface::class, $user1following[1]);
        $this->assertFalse($user1following[1]->isProxyInitialized());
        $this->assertEquals($user3->getId(), $user1following[1]->getId());

        $this->expectException(DocumentNotFoundException::class);
        $user1following[1]->initializeProxy();
    }

    public function testReferenceManyInverseSidePreparesFilterCriteria()
    {
        $class = GH593User::class;

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
        $user3           = $this->dm->find($class, $user3->getId());
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

    /**
     * @ODM\ReferenceMany(targetDocument=GH593User::class, inversedBy="followedBy", storeAs="id")
     *
     * @var Collection<int, GH593User>
     */
    public $following;

    /**
     * @ODM\ReferenceMany(targetDocument=GH593User::class, mappedBy="following")
     *
     * @var Collection<int, GH593User>
     */
    public $followedBy;

    public function __construct()
    {
        $this->following  = new ArrayCollection();
        $this->followedBy = new ArrayCollection();
    }

    /** Return the identifier without triggering Proxy initialization */
    public function getId()
    {
        return $this->id;
    }
}
