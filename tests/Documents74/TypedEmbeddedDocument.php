<?php

declare(strict_types=1);

namespace Documents74;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/** @ODM\EmbeddedDocument() */
class TypedEmbeddedDocument
{
    /** @ODM\Field(type="string") */
    private string $name;

    /** @ODM\Field(type="int") */
    private int $number;

    public function __construct(string $name, int $number)
    {
        $this->name   = $name;
        $this->number = $number;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getNumber(): int
    {
        return $this->number;
    }
}
