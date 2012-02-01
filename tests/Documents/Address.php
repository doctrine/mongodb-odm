<?php

namespace Documents;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/** @ODM\EmbeddedDocument */
class Address
{
    /** @ODM\String */
    private $address;

    /** @ODM\String */
    private $city;

    /** @ODM\String */
    private $state;

    /** @ODM\String */
    private $zipcode;

    /** @ODM\Increment */
    public $count = 0;

    /** @ODM\EmbedOne(targetDocument="Address") */
    private $subAddress;

    /** @ODM\String(name="testFieldName") */
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