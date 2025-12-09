<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests;

use DateTime;
use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\Hydrator\HydratorException;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\PersistentCollection;
use Doctrine\ODM\MongoDB\PersistentCollection\PersistentCollectionInterface;
use Doctrine\ODM\MongoDB\Query\Query;

class HydratorTest extends BaseTestCase
{
    public function testHydrator(): void
    {
        $class = $this->dm->getClassMetadata(HydrationClosureUser::class);

        $user = new HydrationClosureUser();
        $this->dm->getHydratorFactory()->hydrate($user, [
            '_id' => 1,
            'title' => null,
            'name' => 'jon',
            'birthdate' => new DateTime('1961-01-01'),
            'referenceOne' => ['$id' => '1'],
            'referenceMany' => [
                ['$id' => '1'],
                ['$id' => '2'],
            ],
            'embedOne' => ['name' => 'jon'],
            'embedMany' => [
                ['name' => 'jon'],
            ],
        ]);

        self::assertEquals(1, $user->id);
        self::assertNull($user->title);
        self::assertEquals('jon', $user->name);
        self::assertInstanceOf(DateTime::class, $user->birthdate);
        self::assertInstanceOf(HydrationClosureReferenceOne::class, $user->referenceOne);
        self::assertTrue(self::isLazyObject($user->referenceOne));
        self::assertInstanceOf(PersistentCollection::class, $user->referenceMany);
        self::assertTrue(self::isLazyObject($user->referenceMany[0]));
        self::assertTrue(self::isLazyObject($user->referenceMany[1]));
        self::assertInstanceOf(HydrationClosureEmbedOne::class, $user->embedOne);
        self::assertInstanceOf(PersistentCollection::class, $user->embedMany);
        self::assertEquals('jon', $user->embedOne->name);
        self::assertEquals('jon', $user->embedMany[0]->name);
    }

    public function testHydrateProxyWithMissingAssociations(): void
    {
        $user = $this->dm->getReference(HydrationClosureUser::class, 1);
        self::assertTrue(self::isLazyObject($user));

        $this->dm->getHydratorFactory()->hydrate($user, [
            '_id' => 1,
            'title' => null,
            'name' => 'jon',
        ]);

        self::assertEquals(1, $user->id);
        self::assertNull($user->title);
        self::assertEquals('jon', $user->name);
        self::assertNull($user->birthdate);
        self::assertNull($user->referenceOne);
        self::assertInstanceOf(PersistentCollection::class, $user->referenceMany);
        self::assertNull($user->embedOne);
        self::assertInstanceOf(PersistentCollection::class, $user->embedMany);
    }

    public function testReadOnly(): void
    {
        $class = $this->dm->getClassMetadata(HydrationClosureUser::class);

        $user = new HydrationClosureUser();
        $this->dm->getHydratorFactory()->hydrate($user, [
            '_id' => 1,
            'name' => 'maciej',
            'birthdate' => new DateTime('1961-01-01'),
            'embedOne' => ['name' => 'maciej'],
            'embedMany' => [
                ['name' => 'maciej'],
            ],
        ], [Query::HINT_READ_ONLY => true]);

        self::assertFalse($this->uow->isInIdentityMap($user));
        self::assertFalse($this->uow->isInIdentityMap($user->embedOne));
        self::assertFalse($this->uow->isInIdentityMap($user->embedMany[0]));
    }

    public function testEmbedOneWithWrongType(): void
    {
        $user = new HydrationClosureUser();

        $this->expectException(HydratorException::class);
        $this->expectExceptionMessage('Expected association for field "embedOne" in document of type "' . HydrationClosureUser::class . '" to be of type "array", "string" received.');

        $this->dm->getHydratorFactory()->hydrate($user, [
            '_id' => 1,
            'embedOne' => 'jon',
        ]);
    }

    public function testEmbedManyWithWrongType(): void
    {
        $user = new HydrationClosureUser();

        $this->expectException(HydratorException::class);
        $this->expectExceptionMessage('Expected association for field "embedMany" in document of type "' . HydrationClosureUser::class . '" to be of type "array", "string" received.');

        $this->dm->getHydratorFactory()->hydrate($user, [
            '_id' => 1,
            'embedMany' => 'jon',
        ]);
    }

    public function testEmbedManyWithWrongElementType(): void
    {
        $user = new HydrationClosureUser();

        $this->dm->getHydratorFactory()->hydrate($user, [
            '_id' => 1,
            'embedMany' => ['jon'],
        ]);

        self::assertInstanceOf(PersistentCollectionInterface::class, $user->embedMany);

        $this->expectException(HydratorException::class);
        $this->expectExceptionMessage('Expected association item with key "0" for field "embedMany" in document of type "' . HydrationClosureUser::class . '" to be of type "array", "string" received.');

        $user->embedMany->initialize();
    }

    public function testReferenceOneWithWrongType(): void
    {
        $user = new HydrationClosureUser();

        $this->expectException(HydratorException::class);
        $this->expectExceptionMessage('Expected association for field "referenceOne" in document of type "' . HydrationClosureUser::class . '" to be of type "array", "string" received.');

        $this->dm->getHydratorFactory()->hydrate($user, [
            '_id' => 1,
            'referenceOne' => 'jon',
        ]);
    }

    public function testReferenceManyWithWrongType(): void
    {
        $user = new HydrationClosureUser();

        $this->expectException(HydratorException::class);
        $this->expectExceptionMessage('Expected association for field "referenceMany" in document of type "' . HydrationClosureUser::class . '" to be of type "array", "string" received.');

        $this->dm->getHydratorFactory()->hydrate($user, [
            '_id' => 1,
            'referenceMany' => 'jon',
        ]);
    }

    public function testReferenceManyWithWrongElementType(): void
    {
        $user = new HydrationClosureUser();

        $this->dm->getHydratorFactory()->hydrate($user, [
            '_id' => 1,
            'referenceMany' => ['jon'],
        ]);

        self::assertInstanceOf(PersistentCollectionInterface::class, $user->referenceMany);

        $this->expectException(HydratorException::class);
        $this->expectExceptionMessage('Expected association item with key "0" for field "referenceMany" in document of type "' . HydrationClosureUser::class . '" to be of type "array", "string" received.');

        $user->referenceMany->initialize();
    }
}

#[ODM\Document]
class HydrationClosureUser
{
    /** @var string|null */
    #[ODM\Id]
    public $id;

    /** @var string|null */
    #[ODM\Field(type: 'string', nullable: true)]
    public $title = 'Mr.';

    /** @var string|null */
    #[ODM\Field(type: 'string')]
    public $name;

    /** @var DateTime|null */
    #[ODM\Field(type: 'date')]
    public $birthdate;

    /** @var HydrationClosureReferenceOne|null */
    #[ODM\ReferenceOne(targetDocument: HydrationClosureReferenceOne::class)]
    public $referenceOne;

    /** @var Collection<int, HydrationClosureReferenceMany>|array<HydrationClosureReferenceMany> */
    #[ODM\ReferenceMany(targetDocument: HydrationClosureReferenceMany::class)]
    public $referenceMany = [];

    /** @var HydrationClosureEmbedOne|null */
    #[ODM\EmbedOne(targetDocument: HydrationClosureEmbedOne::class)]
    public $embedOne;

    /** @var Collection<int, HydrationClosureEmbedMany>|array<HydrationClosureReferenceMany> */
    #[ODM\EmbedMany(targetDocument: HydrationClosureEmbedMany::class)]
    public $embedMany = [];
}

#[ODM\Document]
class HydrationClosureReferenceOne
{
    /** @var string|null */
    #[ODM\Id]
    public $id;

    /** @var string|null */
    #[ODM\Field(type: 'string')]
    public $name;
}

#[ODM\Document]
class HydrationClosureReferenceMany
{
    /** @var string|null */
    #[ODM\Id]
    public $id;

    /** @var string|null */
    #[ODM\Field(type: 'string')]
    public $name;
}

#[ODM\EmbeddedDocument]
class HydrationClosureEmbedMany
{
    /** @var string|null */
    #[ODM\Field(type: 'string')]
    public $name;
}

#[ODM\EmbeddedDocument]
class HydrationClosureEmbedOne
{
    /** @var string|null */
    #[ODM\Field(type: 'string')]
    public $name;
}
