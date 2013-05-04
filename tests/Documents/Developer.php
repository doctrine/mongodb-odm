<?php

namespace Documents;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * @ODM\Document(db="doctrine_odm_tests", collection="developers")
 */
class Developer
{
    /**
     * @ODM\Id
     */
    private $id;

    /**
     * @ODM\String
     */
    private $name;

    /**
     * @ODM\ReferenceMany(targetDocument="Documents\Project", cascade="all")
     */
    private $projects;

    /**
     * @ODM\ReferenceOne(targetDocument="Documents\Functional\Building", simple=true, cascade="all")
     */
    private $livingBuilding;

    /**
     * @ODM\ReferenceMany(targetDocument="Documents\Functional\Building", simple=true, cascade="all")
     */
    private $visitedBuildings;

    public function __construct($name, Collection $projects = null)
    {
        $this->name = $name;
        $this->projects = null === $projects ? new ArrayCollection() : $projects;
        $this->visitedBuildings = new ArrayCollection();
    }

    public function getId()
    {
        return $this->id;
    }

    public function getProjects()
    {
        return $this->projects;
    }

    public function setLivingBuilding($livingBuilding)
    {
        $this->livingBuilding = $livingBuilding;
    }

    public function getLivingBuilding()
    {
        return $this->livingBuilding;
    }

    public function setVisitedBuildings($visitedBuildings)
    {
        $this->visitedBuildings = $visitedBuildings;
    }

    public function getVisitedBuildings()
    {
        return $this->visitedBuildings;
    }

}
