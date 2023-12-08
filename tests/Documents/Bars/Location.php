<?php

declare(strict_types=1);

namespace Documents\Bars;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

#[ODM\EmbeddedDocument]
class Location
{
    /** @var string|null */
    #[ODM\Field(type: 'string')]
    private $name;

    public function __construct(?string $name = null)
    {
        $this->name = $name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getName(): ?string
    {
        return $this->name;
    }
}
