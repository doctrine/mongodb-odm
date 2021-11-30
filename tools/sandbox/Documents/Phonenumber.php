<?php

declare(strict_types=1);

namespace Documents;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/** @ODM\EmbeddedDocument */
class Phonenumber
{
    /**
     * @ODM\Field(type="string")
     *
     * @var string|null
     */
    protected $phonenumber;

    public function __construct(?string $phonenumber = null)
    {
        $this->phonenumber = $phonenumber;
    }

    public function setPhonenumber(string $phonenumber): void
    {
        $this->phonenumber = $phonenumber;
    }

    public function getPhonenumber(): ?string
    {
        return $this->phonenumber;
    }

    public function __toString(): string
    {
        return (string) $this->phonenumber;
    }
}
