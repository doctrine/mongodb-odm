<?php

declare(strict_types=1);

namespace Documents\Bars;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/** @ODM\EmbeddedDocument */
class Location
{
    /**
     * @ODM\Field(type="string")
     *
     * @var string|null
     */
    private $name;

    public function __construct($name = null)
    {
        $this->name = $name;
    }

    public function setName($name): void
    {
        $this->name = $name;
    }

    public function getName()
    {
        return $this->name;
    }
}
