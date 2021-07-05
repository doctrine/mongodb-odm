<?php

declare(strict_types=1);

namespace Documents;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/** @ODM\Document */
class Manager extends BaseEmployee
{
    /** @ODM\ReferenceMany(targetDocument=Project::class) */
    private $projects = [];

    public function getProjects(): array
    {
        return $this->projects;
    }

    public function addProject(Project $project): void
    {
        $this->projects[] = $project;
    }
}
