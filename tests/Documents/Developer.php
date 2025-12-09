<?php

declare(strict_types=1);

namespace Documents;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

#[ODM\Document]
class Developer
{
    /** @var string|null */
    #[ODM\Id]
    private $id;

    /** @var string */
    #[ODM\Field(type: 'string')]
    private $name;

    /** @var Collection<int, Project> */
    #[ODM\ReferenceMany(targetDocument: Project::class, cascade: 'all')]
    private $projects;

    /** @param Collection<int, Project>|null $projects */
    public function __construct(string $name, ?Collection $projects = null)
    {
        $this->name     = $name;
        $this->projects = $projects ?? new ArrayCollection();
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    /** @return Collection<int, Project> */
    public function getProjects(): Collection
    {
        return $this->projects;
    }
}
