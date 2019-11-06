<?php

declare(strict_types=1);

namespace TestDocuments;

use DateTime;

class AlsoLoadDocument
{
    protected $id;

    protected $createdAt;

    public function __construct()
    {
        $this->createdAt = new DateTime();
    }

    public function getId()
    {
        return $this->id;
    }
}
