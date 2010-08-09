<?php

namespace Documents;

/** @EmbeddedDocument */
class Address
{
    /** @String */
    private $address;

    /** @String */
    private $city;

    /** @String */
    private $state;

    /** @String */
    private $zipcode;

    /** @Increment */
    public $count = 0;

    /** @EmbedOne(targetDocument="Address") */
    private $subAddress;

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