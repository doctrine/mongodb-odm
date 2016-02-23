<?php

namespace Documents;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/** @ODM\EmbeddedDocument */
class Address
{
    /** @ODM\Field(type="string") */
    private $address;

    /** @ODM\Field(type="string") */
    private $city;

    /** @ODM\Field(type="string") */
    private $state;

    /** @ODM\Field(type="string") */
    private $zipcode;

    /** @ODM\Field(type="int", strategy="increment") */
    public $count = 0;

    /** @ODM\EmbedOne(targetDocument="Address") */
    private $subAddress;

    /** @ODM\Field(name="testFieldName", type="string") */
    private $test;

    public function setSubAddress(Address $subAddress)
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

    public function setAddress($address)
    {
        $this->address = $address;
    }

    public function getCity()
    {
        return $this->city;
    }

    public function setCity($city)
    {
        $this->city = $city;
    }

    public function getState()
    {
        return $this->state;
    }

    public function setState($state)
    {
        $this->state = $state;
    }

    public function getZipcode()
    {
        return $this->zipcode;
    }

    public function setZipcode($zipcode)
    {
        $this->zipcode = $zipcode;
    }
}
