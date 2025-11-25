<?php

declare(strict_types=1);

namespace Documentation\Introduction;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\Mapping\Attribute as ODM;

#[ODM\Document]
class Manager extends BaseEmployee
{
    #[ODM\Id]
    public string $id;

    /** @var Collection<int, Project> */
    #[ODM\ReferenceMany(targetDocument: Project::class)]
    public Collection $projects;

    public function __construct()
    {
        $this->projects = new ArrayCollection();
    }
}
