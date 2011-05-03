<?php

namespace Documents;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

/**
 * @Document(db="my_db", collection="developers")
 */
class Developer
{
    /**
     * @Id
     */
    private $id;

    /**
     * @String
     */
    private $name;

    /**
     * @ReferenceMany(targetDocument="Documents\Project", cascade="all")
     */
    private $projects;

    public function __construct($name, Collection $projects = null)
    {
        $this->name = $name;
        $this->projects = null === $projects ? new ArrayCollection() : $projects;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getProjects()
    {
        return $this->projects;
    }
}
