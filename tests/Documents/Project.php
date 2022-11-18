<?php

declare(strict_types=1);

namespace Documents;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * @ODM\Document
 * @ODM\InheritanceType("SINGLE_COLLECTION")
 * @ODM\DiscriminatorField("type")
 * @ODM\DiscriminatorMap({"project"="Documents\Project", "sub-project"="Documents\SubProject", "other-sub-project"="Documents\OtherSubProject"})
 */
class Project
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
     * @var string|null
     */
    private $name;

    /**
     * @ODM\EmbedOne(targetDocument=Address::class)
     *
     * @var Address|null
     */
    private $address;

    /**
     * @ODM\ReferenceMany(targetDocument=SubProject::class, cascade="all")
     *
     * @var Collection<int, SubProject>
     */
    private $subProjects;

    /** @param Collection<int, SubProject>|null $subProjects */
    public function __construct(string $name, ?Collection $subProjects = null)
    {
        $this->name        = $name;
        $this->subProjects = $subProjects ?? new ArrayCollection();
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function setAddress(Address $address): void
    {
        $this->address = $address;
    }

    public function getAddress(): ?Address
    {
        return $this->address;
    }

    /** @param Collection<int, SubProject> $subProjects */
    public function setSubProjects(Collection $subProjects): void
    {
        $this->subProjects = $subProjects;
    }

    /** @return Collection<int, SubProject> */
    public function getSubProjects()
    {
        return $this->subProjects;
    }
}
