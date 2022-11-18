<?php

declare(strict_types=1);

namespace Documents;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/** @ODM\Document */
class Developer
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
     * @var string
     */
    private $name;

    /**
     * @ODM\ReferenceMany(targetDocument=Project::class, cascade="all")
     *
     * @var Collection<int, Project>
     */
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
