<?php

declare(strict_types=1);

namespace Documents;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

#[ODM\EmbeddedDocument]
class Address
{
    /** @var string|null */
    #[ODM\Field(type: 'string')]
    private $address;

    /** @var string|null */
    #[ODM\Field(type: 'string')]
    private $city;

    /** @var string|null */
    #[ODM\Field(type: 'string')]
    private $state;

    /** @var string|null */
    #[ODM\Field(type: 'string')]
    private $zipcode;

    /** @var int */
    #[ODM\Field(type: 'int', strategy: 'increment')]
    public $count = 0;

    /** @var Address|null */
    #[ODM\EmbedOne(targetDocument: self::class)]
    private $subAddress;

    /** @var string|null */
    #[ODM\Field(name: 'testFieldName', type: 'string')]
    private $test;

    public function setSubAddress(Address $subAddress): void
    {
        $this->subAddress = $subAddress;
    }

    public function getSubAddress(): ?Address
    {
        return $this->subAddress;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(string $address): void
    {
        $this->address = $address;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setCity(string $city): void
    {
        $this->city = $city;
    }

    public function getState(): ?string
    {
        return $this->state;
    }

    public function setState(string $state): void
    {
        $this->state = $state;
    }

    public function getZipcode(): ?string
    {
        return $this->zipcode;
    }

    public function setZipcode(string $zipcode): void
    {
        $this->zipcode = $zipcode;
    }
}
