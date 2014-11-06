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
}

/**
 * @ODM\Document
 * @ODM\InheritanceType("SINGLE_COLLECTION")
 * @ODM\DiscriminatorField(fieldName="type")
 * @ODM\DiscriminatorMap({"car"="Car", "bicycle"="Bicycle"})
 */
class Vehicle
{
    /** @ODM\Id */
    public $id;

    /** @ODM\String */
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