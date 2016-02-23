<?php


namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

class GH971Test extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    public function testUpdateOfInheritedDocumentUsingFindAndUpdate()
    {
        $name = "Ferrari";
        $features = array(
            "Super Engine",
            "Huge Wheels"
        );

        //first query, create Car with name "Ferrari"
        $this->dm->createQueryBuilder(__NAMESPACE__ . '\Car')
            ->findAndUpdate()
            ->upsert(true)
            ->field('name')->equals($name)
            ->sort('_id', -1)
            ->field('features')->push($features[0])
            ->getQuery()->execute();

        //second query: update existing "Ferrari" with new feature
        $this->dm->createQueryBuilder(__NAMESPACE__ . '\Car')
            ->findAndUpdate()
            ->upsert(true)
            ->field('name')->equals($name)
            ->sort('_id', -1)
            ->field('features')->push($features[1])
            ->getQuery()->execute();

        $results = $this->dm->getRepository(__NAMESPACE__ . '\Car')->findAll();
        $this->assertCount(1, $results);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Upsert query that is to be performed on discriminated document does not have single discriminator. Either not use base class or set 'type' field manually.
     */
    public function testUpsertThrowsExceptionWithIndecisiveDiscriminator()
    {
        $this->dm->createQueryBuilder(__NAMESPACE__ . '\Bicycle')
            ->findAndUpdate()
            ->upsert(true)
            ->field('name')->equals("Cool")
            ->field('features')->push("2 people")
            ->getQuery()->execute();
    }

    public function testUpsertWillUseProvidedDiscriminator()
    {
        $this->dm->createQueryBuilder(__NAMESPACE__ . '\Bicycle')
            ->findAndUpdate()
            ->upsert(true)
            ->field('type')->equals('tandem')
            ->field('name')->equals("Cool")
            ->field('features')->push("2 people")
            ->getQuery()->execute();

        $results = $this->dm->getRepository(__NAMESPACE__ . '\Tandem')->findAll();
        $this->assertCount(1, $results);
    }
}

/**
 * @ODM\Document
 * @ODM\InheritanceType("SINGLE_COLLECTION")
 * @ODM\DiscriminatorField(fieldName="type")
 * @ODM\DiscriminatorMap({"car"="Car", "bicycle"="Bicycle", "tandem"="Tandem"})
 */
class Vehicle
{
    /** @ODM\Id */
    public $id;

    /** @ODM\Field(type="string") */
    public $name;

    /** @ODM\EmbedMany */
    public $features;
}

/**
 * @ODM\Document
 */
class Car extends Vehicle {}

/**
 * @ODM\Document
 */
class Bicycle extends Vehicle {}

/**
 * @ODM\Document
 */
class Tandem extends Bicycle {}
