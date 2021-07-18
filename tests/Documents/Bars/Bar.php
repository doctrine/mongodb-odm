<?php

declare(strict_types=1);

namespace Documents\Bars;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/** @ODM\Document(collection="bars") */
class Bar
{
    /** @ODM\Id */
    private $id;

    /** @ODM\Field(type="string") */
    private $name;

    /**
     * @ODM\EmbedMany(targetDocument=Documents\Bars\Location::class)
     *
     * @var Collection<int, Location>
     */
    private $locations;

    public function __construct($name = null)
    {
        $this->name      = $name;
        $this->locations = new ArrayCollection();
    }

    public function getId()
    {
        return $this->id;
    }

    public function setName($name): void
    {
        $this->name = $name;
    }

    public function getName()
    {
        return $this->name;
    }

    public function addLocation(Location $location): void
    {
        $this->locations[] = $location;
    }

    public function getLocations(): Collection
    {
        return $this->locations;
    }

    public function setLocations($locations): void
    {
        $this->locations = $locations;
    }
}
