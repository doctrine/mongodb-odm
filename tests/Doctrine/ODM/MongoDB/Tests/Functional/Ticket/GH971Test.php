<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Tests\BaseTest;
use InvalidArgumentException;

class GH971Test extends BaseTest
{
    public function testUpdateOfInheritedDocumentUsingFindAndUpdate(): void
    {
        $name     = 'Ferrari';
        $features = [
            'Super Engine',
            'Huge Wheels',
        ];

        //first query, create Car with name "Ferrari"
        $this->dm->createQueryBuilder(Car::class)
            ->findAndUpdate()
            ->upsert(true)
            ->field('name')->equals($name)
            ->sort('_id', -1)
            ->field('features')->push($features[0])
            ->getQuery()->execute();

        //second query: update existing "Ferrari" with new feature
        $this->dm->createQueryBuilder(Car::class)
            ->findAndUpdate()
            ->upsert(true)
            ->field('name')->equals($name)
            ->sort('_id', -1)
            ->field('features')->push($features[1])
            ->getQuery()->execute();

        $results = $this->dm->getRepository(Car::class)->findAll();
        self::assertCount(1, $results);
    }

    public function testUpsertThrowsExceptionWithIndecisiveDiscriminator(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Upsert query that is to be performed on discriminated document does not have single discriminator. ' .
            'Either not use base class or set \'type\' field manually.',
        );
        $this->dm->createQueryBuilder(Bicycle::class)
            ->findAndUpdate()
            ->upsert(true)
            ->field('name')->equals('Cool')
            ->field('features')->push('2 people')
            ->getQuery()->execute();
    }

    public function testUpsertWillUseProvidedDiscriminator(): void
    {
        $this->dm->createQueryBuilder(Bicycle::class)
            ->findAndUpdate()
            ->upsert(true)
            ->field('type')->equals('tandem')
            ->field('name')->equals('Cool')
            ->field('features')->push('2 people')
            ->getQuery()->execute();

        $results = $this->dm->getRepository(Tandem::class)->findAll();
        self::assertCount(1, $results);
    }
}

/**
 * @ODM\Document
 * @ODM\InheritanceType("SINGLE_COLLECTION")
 * @ODM\DiscriminatorField("type")
 * @ODM\DiscriminatorMap({"car"=Car::class, "bicycle"=Bicycle::class, "tandem"=Tandem::class})
 */
class Vehicle
{
    /**
     * @ODM\Id
     *
     * @var string|null
     */
    public $id;

    /**
     * @ODM\Field(type="string")
     *
     * @var string|null
     */
    public $name;

    /**
     * @ODM\EmbedMany
     *
     * @var Collection<int, object>
     */
    public $features;
}

/** @ODM\Document */
class Car extends Vehicle
{
}

/** @ODM\Document */
class Bicycle extends Vehicle
{
}

/** @ODM\Document */
class Tandem extends Bicycle
{
}
