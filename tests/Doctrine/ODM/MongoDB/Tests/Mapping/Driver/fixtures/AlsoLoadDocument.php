<?php

namespace TestDocuments;

class AlsoLoadDocument
{
    protected $id;

    protected $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
    }

    public function getId()
    {
        return $this->id;
    }
}
