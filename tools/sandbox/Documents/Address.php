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
    protected $street;

    /**
     * @ODM\Field(type="string")
     *
     * @var string|null
     */
    protected $city;

    /**
     * @ODM\Field(type="string")
     *
     * @var string|null
     */
    protected $state;

    /**
     * @ODM\Field(type="string")
     *
     * @var string|null
     */
    protected $postalCode;

    public function getStreet(): ?string
    {
        return $this->street;
    }

    public function setStreet(string $street): void
    {
        $this->street = $street;
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

    public function getPostalCode(): ?string
    {
        return $this->postalCode;
    }

    public function setPostalCode(string $postalCode): void
    {
        $this->postalCode = $postalCode;
    }
}
