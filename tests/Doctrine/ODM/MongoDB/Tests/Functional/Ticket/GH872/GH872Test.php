<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket\GH872;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

use Doctrine\Common\Collections\ArrayCollection;

class GH872Test extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    public function tearDown() {
        parent::tearDown();
    }
    
    public function testReferencedInheritanceTreeWorksCorrectly()
    {
        $event = new Event('My Event');
        $location = new CityLocation('City Location');
        $location->getAddress()->setStreetName('Some address');
        $event->setLocation($location);

        // persist & flush
        $this->dm->persist($location);
        $this->dm->persist($event);
        
        $eventId = $event->id;
        $locationId = $location->id;
        
        $this->dm->flush();
        $this->dm->clear();

        //
        $locationColl = $this->dm->getDocumentCollection(__NAMESPACE__ . '\Location')->getMongoCollection();
        $locationDoc = $locationColl->findOne(array('_id' => new \MongoId($locationId)));
        $this->assertEquals('c', $locationDoc['type']);
        $this->assertEquals('Some address', $locationDoc['a']['streetName']);
        
        //
        $eventColl = $this->dm->getDocumentCollection(__NAMESPACE__ . '\Event')->getMongoCollection();
        $eventDoc = $eventColl->findOne(array('_id' => new \MongoId($eventId)));

        $this->assertEquals((string)$locationId, (string)$eventDoc['location']);
        
        $event = $this->dm->find(__NAMESPACE__ . '\Event', $eventId);

        $location = $event->getLocation();
        $this->assertNotNull($location);
        $this->assertEquals('City Location', $location->name);
        
        $address = $location->getAddress();
        $this->assertNotNull($address);

        $this->assertEquals('Some address', $address->getStreetName());
    }
}

/** @ODM\Document */
class Event
{
    /** @ODM\Id */
    public $id;

    /** @ODM\String */
    public $name;

    /** @ODM\ReferenceOne(targetDocument="Location", simple="true") */
    protected $location;
    
    public function getLocation() {
        return $this->location;
    }
    
    public function setLocation($location) {
        $this->location = $location;
    }

    public function __construct($name)
    {
        $this->name = $name;
    }
}

/**
 * @ODM\Document
 * @ODM\InheritanceType("SINGLE_COLLECTION")
 * @ODM\DiscriminatorField(fieldName="type")
 * @ODM\DiscriminatorMap({
 *   "g"="Doctrine\ODM\MongoDB\Tests\Functional\Ticket\GH872\GenericLocation",
 *   "c"="Doctrine\ODM\MongoDB\Tests\Functional\Ticket\GH872\CityLocation",
 * })
 */
class Location
{
    /** @ODM\Id */
    public $id;

    /** @ODM\String */
    public $name;

    /** @ODM\EmbedOne(name="a", targetDocument="Address") */
    protected $address;
    
    public function getAddress() {
        return $this->address;
    }

    public function __construct($name)
    {
        $this->name = $name;
        $this->address = new Address();
    }
}

/**
 * @ODM\Document
 */
class GenericLocation extends Location
{
    /** @ODM\String */
    public $description;
}

/**
 * @ODM\Document
 */
class CityLocation extends Location
{
    /** @ODM\String */
    public $cityName;
}

/** @ODM\EmbeddedDocument */
class Address
{
    /** @ODM\String */
    protected $streetName;
    
    public function getStreetName() {
        return $this->streetName;
    }
    
    public function setStreetName($streetName) {
        $this->streetName = $streetName;
    }
}
