<?php

declare(strict_types=1);

namespace Documents\GraphLookup;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

#[ODM\Document]
class Traveller
{
    /** @var string|null */
    #[ODM\Id]
    public $id;

    /** @var string */
    #[ODM\Field(type: 'string')]
    public $name;

    /** @var Airport|null */
    #[ODM\ReferenceOne(targetDocument: Airport::class, cascade: ['persist'], storeAs: 'ref')]
    public $nearestAirport;

    /** @var Airport|null */
    #[ODM\ReferenceOne(targetDocument: Airport::class, cascade: ['persist'], storeAs: 'id')]
    public $nearestAirportId;

    public function __construct(string $name, Airport $nearestAirport)
    {
        $this->name             = $name;
        $this->nearestAirport   = $nearestAirport;
        $this->nearestAirportId = $nearestAirport;
    }
}
