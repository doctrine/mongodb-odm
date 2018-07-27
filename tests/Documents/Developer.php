<?php

declare(strict_types=1);

namespace Documents;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * @ODM\Document
 */
class Developer
{
    /** @ODM\Id */
    private $id;

    /** @ODM\Field(type="string") */
    private $name;

    /** @ODM\ReferenceMany(targetDocument=Project::class, cascade="all") */
    private $projects;

    public function __construct($name, ?Collection $projects = null)
    {
        $this->name = $name;
        $this->projects = $projects === null ? new ArrayCollection() : $projects;
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
