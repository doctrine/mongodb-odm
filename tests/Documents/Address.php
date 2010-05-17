<?php

namespace Documents;

use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;

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