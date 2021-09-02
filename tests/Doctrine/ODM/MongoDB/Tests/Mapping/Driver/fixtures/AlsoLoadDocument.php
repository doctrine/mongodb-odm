<?php

declare(strict_types=1);

namespace TestDocuments;

use DateTime;

class AlsoLoadDocument
{
    /** @var string|null */
    protected $id;

    /** @var DateTime */
    protected $createdAt;

    public function __construct()
    {
        $this->createdAt = new DateTime();
    }

    public function getId(): ?string
    {
        return $this->id;
    }
}
