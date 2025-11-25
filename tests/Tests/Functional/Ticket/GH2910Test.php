<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\ODM\MongoDB\Mapping\Attribute as ODM;
use Doctrine\ODM\MongoDB\Tests\BaseTestCase;

class GH2910Test extends BaseTestCase
{
    public function testBirdCollectionNameIsAnimals(): void
    {
        $this->assertEquals('animals', $this->dm->getRepository(Bird2910::class)->getClassMetadata()->getCollection());
    }

    public function testDogCollectionNameIsAnimals(): void
    {
        $this->assertEquals('animals', $this->dm->getRepository(Dog2910::class)->getClassMetadata()->getCollection());
    }
}

#[ODM\MappedSuperclass(collection: 'animals')]
#[ODM\InheritanceType('SINGLE_COLLECTION')]
#[ODM\DiscriminatorField('classification')]
#[ODM\DiscriminatorMap(['bird' => Bird2910::class, 'dog' => Dog2910::class])]
class Animal2910
{
    #[ODM\Id()]
    private string $id;
}

#[ODM\Document]
class Bird2910 extends Animal2910
{
}

#[ODM\Document]
class Dog2910 extends Animal2910
{
}
