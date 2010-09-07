<?php

namespace Documents\Bars;

/** @Document(collection="bars") */
class Bar
{
    /** @Id */
    private $id;

    /** @String */
    private $name;

    /** @EmbedMany(targetDocument="Documents\Bars\Location") */
    private $locations = array();

    public function __construct($name = null)
    {
        $this->name = $name;
    }

    public function getId()
    {
        return $this->id;
    }

    public function setName($name)
    {
        $this->name = $name;
    }

    public function getName()
    {
        return $this->name;
    }

    public function addLocation(Location $location)
    {
        $this->locations[] = $location;
    }

    public function getLocations()
    {
        return $this->locations;
    }
}