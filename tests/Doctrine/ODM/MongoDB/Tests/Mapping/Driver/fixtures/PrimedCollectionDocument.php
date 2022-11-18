<?php

declare(strict_types=1);

namespace TestDocuments;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

class PrimedCollectionDocument
{
    /** @var string|null */
    protected $id;

    /** @var Collection<int, PrimedCollectionDocument> */
    protected $inverseMappedBy;

    /** @var Collection<int, PrimedCollectionDocument> */
    protected $references;

    public function __construct()
    {
        $this->inverseMappedBy = new ArrayCollection();
        $this->references      = new ArrayCollection();
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    /** @return Collection<int, PrimedCollectionDocument> */
    public function getInverseMappedBy(): Collection
    {
        return $this->inverseMappedBy;
    }

    /** @return Collection<int, PrimedCollectionDocument> */
    public function getReferences(): Collection
    {
        return $this->references;
    }
}
