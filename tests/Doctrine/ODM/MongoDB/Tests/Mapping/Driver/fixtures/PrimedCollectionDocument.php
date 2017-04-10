<?php

namespace TestDocuments;

use Doctrine\Common\Collections\ArrayCollection;

class PrimedCollectionDocument
{
    protected $id;

    protected $inverseMappedBy;

    protected $references;

    public function __construct()
    {
        $this->inverseMappedBy = new ArrayCollection();
        $this->references = new ArrayCollection();
    }

    public function getId()
    {
        return $this->id;
    }

    public function getInverseMappedBy()
    {
        return $this->inverseMappedBy;
    }

    public function getReferences()
    {
        return $this->references;
    }
}
