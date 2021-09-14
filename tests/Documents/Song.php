<?php

declare(strict_types=1);

namespace Documents;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/** @ODM\EmbeddedDocument */
class Song
{
    /**
     * @ODM\Field(type="string")
     *
     * @var string|null
     */
    private $name;

    public function __construct($name)
    {
        $this->name = $name;
    }

    public function setName($name): void
    {
        $this->name = $name;
    }

    public function getName(): ?string
    {
        return $this->name;
    }
}
