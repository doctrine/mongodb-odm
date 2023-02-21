<?php

declare(strict_types=1);

namespace Documents;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/** @ODM\Document */
class ViewReference
{
    /**
     * @ODM\Id
     *
     * @var string
     */
    private $id;

    /**
     * @ODM\ReferenceOne(targetDocument=UserName::class, cascade={"persist"})
     *
     * @var UserName
     */
    private $referenceOneView;

    /**
     * @ODM\ReferenceOne(targetDocument=UserName::class, mappedBy="viewReference")
     *
     * @var UserName
     */
    private $referenceOneViewMappedBy;

    /**
     * @ODM\ReferenceMany(targetDocument=UserName::class, cascade={"persist"})
     *
     * @var Collection<int, UserName>
     */
    private $referenceManyView;

    /**
     * @ODM\ReferenceMany(targetDocument=UserName::class, mappedBy="viewReference")
     *
     * @var Collection<int, UserName>
     */
    private $referenceManyViewMappedBy;

    public function __construct(string $id)
    {
        $this->id                        = $id;
        $this->referenceManyView         = new ArrayCollection();
        $this->referenceManyViewMappedBy = new ArrayCollection();
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getReferenceOneView(): ?UserName
    {
        return $this->referenceOneView;
    }

    public function getReferenceOneViewMappedBy(): ?UserName
    {
        return $this->referenceOneViewMappedBy;
    }

    /** @return Collection<int, UserName> */
    public function getReferenceManyView(): Collection
    {
        return $this->referenceManyView;
    }

    /** @return Collection<int, UserName> */
    public function getReferenceManyViewMappedBy(): Collection
    {
        return $this->referenceManyViewMappedBy;
    }

    public function setReferenceOneView(?UserName $userName): void
    {
        $this->referenceOneView = $userName;
    }

    public function addReferenceManyView(UserName $userName): void
    {
        $this->referenceManyView->add($userName);
    }
}
