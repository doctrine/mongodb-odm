<?php

namespace Documents\GraphLookup;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * @ODM\Document
 */
class Traveller
{
    /** @ODM\Id */
    public $id;

    /** @ODM\Field(type="string") */
    public $name;

    /**
     * @ODM\ReferenceOne(targetDocument=Airport::class, cascade={"persist"}, storeAs="ref")
     */
    public $nearestAirport;

    /**
     * @ODM\ReferenceOne(targetDocument=Airport::class, cascade={"persist"}, storeAs="id")
     */
    public $nearestAirportId;

    public function __construct($name, Airport $nearestAirport)
    {
        $this->name = $name;
        $this->nearestAirport = $nearestAirport;
        $this->nearestAirportId = $nearestAirport;
    }
}
