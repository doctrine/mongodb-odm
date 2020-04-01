<?php

declare(strict_types=1);

namespace Documents;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/** @ODM\Document */
class ViewReference
{
    /** @ODM\Id */
    private $id;

    /** @ODM\ReferenceOne(targetDocument=UserName::class, cascade={"persist"}) */
    private $referenceOneView;

    /** @ODM\ReferenceOne(targetDocument=UserName::class, mappedBy="viewReference") */
    private $referenceOneViewMappedBy;

    /** @ODM\ReferenceMany(targetDocument=UserName::class, cascade={"persist"}) */
    private $referenceManyView;

    /** @ODM\ReferenceMany(targetDocument=UserName::class, mappedBy="viewReference") */
    private $referenceManyViewMappedBy;

    public function __construct(string $id)
    {
        $this->id                        = $id;
        $this->referenceManyView         = new ArrayCollection();
        $this->referenceManyViewMappedBy = new ArrayCollection();
    }

    public function getId() : string
    {
        return $this->id;
    }

    public function getReferenceOneView() : ?UserName
    {
        return $this->referenceOneView;
    }

    public function getReferenceOneViewMappedBy() : ?UserName
    {
        return $this->referenceOneViewMappedBy;
    }

    public function getReferenceManyView() : Collection
    {
        return $this->referenceManyView;
    }

    public function getReferenceManyViewMappedBy() : Collection
    {
        return $this->referenceManyViewMappedBy;
    }

    public function setReferenceOneView(?UserName $userName) : void
    {
        $this->referenceOneView = $userName;
    }

    public function addReferenceManyView(UserName $userName) : void
    {
        $this->referenceManyView->add($userName);
    }
}
