<?php

declare(strict_types=1);

namespace Documents\Bars;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/** @ODM\Document(collection="bars") */
class Bar
{
    /**
     * @ODM\Id
     *
     * @var string|null
     */
    private $id;

    /**
     * @ODM\Field(type="string")
     *
     * @var string|null
     */
    private $name;

    /**
     * @ODM\EmbedMany(targetDocument=Documents\Bars\Location::class)
     *
     * @var Collection<int, Location>
     */
    private $locations;

    public function __construct(?string $name = null)
    {
        $this->name      = $name;
        $this->locations = new ArrayCollection();
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function addLocation(Location $location): void
    {
        $this->locations[] = $location;
    }

    /** @return Collection<int, Location> */
    public function getLocations(): Collection
    {
        return $this->locations;
    }

    /** @param Collection<int, Location> $locations */
    public function setLocations(Collection $locations): void
    {
        $this->locations = $locations;
    }
}
