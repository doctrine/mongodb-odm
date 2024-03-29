<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Tools\GH297;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/** @ODM\EmbeddedDocument */
class Address
{
    /**
     * @ODM\Field(type="string")
     *
     * @var string|null
     */
    private $street;

    public function getStreet(): ?string
    {
        return $this->street;
    }

    public function setStreet(string $street): void
    {
        $this->street = $street;
    }
}
