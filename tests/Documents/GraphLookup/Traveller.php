<?php

declare(strict_types=1);

namespace Documents\GraphLookup;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/** @ODM\Document */
class Traveller
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
     * @var string
     */
    public $name;

    /**
     * @ODM\ReferenceOne(targetDocument=Airport::class, cascade={"persist"}, storeAs="ref")
     *
     * @var Airport|null
     */
    public $nearestAirport;

    /**
     * @ODM\ReferenceOne(targetDocument=Airport::class, cascade={"persist"}, storeAs="id")
     *
     * @var Airport|null
     */
    public $nearestAirportId;

    public function __construct(string $name, Airport $nearestAirport)
    {
        $this->name             = $name;
        $this->nearestAirport   = $nearestAirport;
        $this->nearestAirportId = $nearestAirport;
    }
}
