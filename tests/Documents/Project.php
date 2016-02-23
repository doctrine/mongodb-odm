<?php

namespace Documents;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * @ODM\Document
 * @ODM\InheritanceType("SINGLE_COLLECTION")
 * @ODM\DiscriminatorField(fieldName="type")
 * @ODM\DiscriminatorMap({"project"="Documents\Project", "sub-project"="Documents\SubProject", "other-sub-project"="Documents\OtherSubProject"})
 */
class Project
{
    /** @ODM\Id */
    private $id;

    /** @ODM\Field(type="string") */
    private $name;

    /** @ODM\EmbedOne(targetDocument="Address") */
    private $address;

    /**
     * @ODM\ReferenceMany(targetDocument="SubProject", cascade="all")
     */
    private $subProjects;

    public function __construct($name, Collection $subProjects = null)
    {
        $this->name = $name;
        $this->subProjects = $subProjects ? $subProjects : new ArrayCollection();
    }

    public function getId()
    {
        return $this->id;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setName($name)
    {
        $this->name = $name;
    }

    public function setAddress(Address $address)
    {
        $this->address = $address;
    }

    public function getAddress()
    {
        return $this->address;
    }

    public function setSubProjects(Collection $subProjects)
    {
        $this->subProjects = $subProjects;
    }

    public function getSubProjects()
    {
        return $this->subProjects;
    }
}
