<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests;

use DateTime;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\PersistentCollection;
use Doctrine\ODM\MongoDB\Proxy\Proxy;
use Doctrine\ODM\MongoDB\Query\Query;

class HydratorTest extends BaseTest
{
    public function testHydrator()
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

        $this->assertEquals(1, $user->id);
        $this->assertNull($user->title);
        $this->assertEquals('jon', $user->name);
        $this->assertInstanceOf(DateTime::class, $user->birthdate);
        $this->assertInstanceOf(HydrationClosureReferenceOne::class, $user->referenceOne);
        $this->assertInstanceOf(PersistentCollection::class, $user->referenceMany);
        $this->assertInstanceOf(Proxy::class, $user->referenceMany[0]);
        $this->assertInstanceOf(Proxy::class, $user->referenceMany[1]);
        $this->assertInstanceOf(PersistentCollection::class, $user->embedMany);
        $this->assertEquals('jon', $user->embedOne->name);
        $this->assertEquals('jon', $user->embedMany[0]->name);
    }

    public function testReadOnly()
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
        ], [ Query::HINT_READ_ONLY => true ]);

        $this->assertFalse($this->uow->isInIdentityMap($user));
        $this->assertFalse($this->uow->isInIdentityMap($user->embedOne));
        $this->assertFalse($this->uow->isInIdentityMap($user->embedMany[0]));
    }
}

/** @ODM\Document */
class HydrationClosureUser
{
    /** @ODM\Id */
    public $id;

    /** @ODM\Field(type="string", nullable=true) */
    public $title = 'Mr.';

    /** @ODM\Field(type="string") */
    public $name;

    /** @ODM\Field(type="date") */
    public $birthdate;

    /** @ODM\ReferenceOne(targetDocument=HydrationClosureReferenceOne::class) */
    public $referenceOne;

    /** @ODM\ReferenceMany(targetDocument=HydrationClosureReferenceMany::class) */
    public $referenceMany = [];

    /** @ODM\EmbedOne(targetDocument=HydrationClosureEmbedOne::class) */
    public $embedOne;

    /** @ODM\EmbedMany(targetDocument=HydrationClosureEmbedMany::class) */
    public $embedMany = [];
}

/** @ODM\Document */
class HydrationClosureReferenceOne
{
    /** @ODM\Id */
    public $id;

    /** @ODM\Field(type="string") */
    public $name;
}

/** @ODM\Document */
class HydrationClosureReferenceMany
{
    /** @ODM\Id */
    public $id;

    /** @ODM\Field(type="string") */
    public $name;
}

/** @ODM\EmbeddedDocument */
class HydrationClosureEmbedMany
{
    /** @ODM\Field(type="string") */
    public $name;
}

/** @ODM\EmbeddedDocument */
class HydrationClosureEmbedOne
{
    /** @ODM\Field(type="string") */
    public $name;
}
