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

    public function getSubAddress()
    {
        return $this->subAddress;
    }

    public function getAddress()
    {
        return $this->address;
    }

    public function setAddress($address): void
    {
        $this->address = $address;
    }

    public function getCity()
    {
        return $this->city;
    }

    public function setCity($city): void
    {
        $this->city = $city;
    }

    public function getState()
    {
        return $this->state;
    }

    public function setState($state): void
    {
        $this->state = $state;
    }

    public function getZipcode()
    {
        return $this->zipcode;
    }

    public function setZipcode($zipcode): void
    {
        $this->zipcode = $zipcode;
    }
}
