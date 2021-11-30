<?php

declare(strict_types=1);

namespace Documents;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/** @ODM\EmbeddedDocument */
class Address
{
    /**
     * @ODM\Field(type="string")
     *
     * @var string|null
     */
    private $address;

    /**
     * @ODM\Field(type="string")
     *
     * @var string|null
     */
    private $city;

    /**
     * @ODM\Field(type="string")
     *
     * @var string|null
     */
    private $state;

    /**
     * @ODM\Field(type="string")
     *
     * @var string|null
     */
    private $zipcode;

    /**
     * @ODM\Field(type="int", strategy="increment")
     *
     * @var int
     */
    public $count = 0;

    /**
     * @ODM\EmbedOne(targetDocument=Address::class)
     *
     * @var Address|null
     */
    private $subAddress;

    /**
     * @ODM\Field(name="testFieldName", type="string")
     *
     * @var string|null
     */
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
