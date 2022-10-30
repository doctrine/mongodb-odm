<?php

declare(strict_types=1);

namespace Documents;

use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/** @ODM\Document */
class Manager extends BaseEmployee
{
    /**
     * @ODM\ReferenceMany(targetDocument=Project::class)
     *
     * @var Collection<int, Project>|array<Project>
     */
    private $projects = [];

    /** @return Collection<int, Project>|array<Project> */
    public function getProjects()
    {
        return $this->projects;
    }

    public function addProject(Project $project): void
    {
        $this->projects[] = $project;
    }
}
