<?php

declare(strict_types=1);

namespace TestDocuments;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

class PrimedCollectionDocument
{
    protected $id;

    protected $inverseMappedBy;

    protected $references;

    public function __construct()
    {
        $this->inverseMappedBy = new ArrayCollection();
        $this->references      = new ArrayCollection();
    }

    public function getId()
    {
        return $this->id;
    }

    public function getInverseMappedBy(): Collection
    {
        return $this->inverseMappedBy;
    }

    public function getReferences(): Collection
    {
        return $this->references;
    }
}
