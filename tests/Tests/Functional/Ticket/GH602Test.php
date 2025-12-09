<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\DocumentNotFoundException;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Tests\BaseTestCase;

use function iterator_to_array;

class GH602Test extends BaseTestCase
{
    public function testReferenceManyOwningSidePreparesFilterCriteriaForDifferentClass(): void
    {
        $thingClass = GH602Thing::class;
        $userClass  = GH602User::class;
        $this->enableDeletedFilter($thingClass);

        $user1  = new GH602User();
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
        $user1      = $this->dm->find($userClass, $user1->getId());
        $user1likes = iterator_to_array($user1->likes, false);

        /* FilterCollection criteria will only be considered upon initialization
         * of the Proxy object, so expect an exception at that time. This is not
         * ideal, but it is the current behavior for hydrating the owning side
         * of a reference-many collection.
         */
        self::assertCount(2, $user1likes);

        self::assertTrue(self::isLazyObject($user1likes[0]));
        self::assertFalse($this->uow->isUninitializedObject($user1likes[0]));
        self::assertEquals($thing1->getId(), $user1likes[0]->getId());

        self::assertTrue(self::isLazyObject($user1likes[1]));
        self::assertTrue($this->uow->isUninitializedObject($user1likes[1]));
        self::assertEquals($thing2->getId(), $user1likes[1]->getId());

        $this->expectException(DocumentNotFoundException::class);
        $this->uow->initializeObject($user1likes[1]);
    }

    public function testReferenceManyInverseSidePreparesFilterCriteriaForDifferentClass(): void
    {
        $thingClass = GH602Thing::class;
        $userClass  = GH602User::class;
        $this->enableDeletedFilter($userClass);

        $user1  = new GH602User();
        $user2  = new GH602User();
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
        $thing1        = $this->dm->find($thingClass, $thing1->getId());
        $thing1likedBy = iterator_to_array($thing1->likedBy, false);

        self::assertCount(1, $thing1likedBy);
        self::assertEquals($user1->getId(), $thing1likedBy[0]->getId());
    }

    /** @psaml-param class-string $class */
    private function enableDeletedFilter(string $class): void
    {
        $this->dm->getFilterCollection()->enable('testFilter');
        $filter = $this->dm->getFilterCollection()->getFilter('testFilter');
        $filter->setParameter('class', $class);
        $filter->setParameter('field', 'deleted');
        $filter->setParameter('value', false);
    }
}

#[ODM\Document]
class GH602User
{
    /** @var string|null */
    #[ODM\Id]
    public $id;

    /** @var bool */
    #[ODM\Field(name: 'user_deleted', type: 'bool')]
    public $deleted = false;

    /** @var Collection<int, GH602Thing> */
    #[ODM\ReferenceMany(targetDocument: GH602Thing::class, inversedBy: 'likedBy', storeAs: 'id')]
    public $likes;

    public function __construct()
    {
        $this->likes = new ArrayCollection();
    }

    /** Return the identifier without triggering Proxy initialization */
    public function getId(): ?string
    {
        return $this->id;
    }
}

#[ODM\Document]
class GH602Thing
{
    /** @var string|null */
    #[ODM\Id]
    public $id;

    /** @var bool */
    #[ODM\Field(name: 'thing_deleted', type: 'bool')]
    public $deleted = false;

    /** @var Collection<int, GH602User> */
    #[ODM\ReferenceMany(targetDocument: GH602User::class, mappedBy: 'likes')]
    public $likedBy;

    public function __construct()
    {
        $this->likedBy = new ArrayCollection();
    }

    /** Return the identifier without triggering Proxy initialization */
    public function getId(): ?string
    {
        return $this->id;
    }
}
