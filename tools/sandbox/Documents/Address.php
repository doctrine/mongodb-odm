<?php

namespace Documents;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/** @ODM\EmbeddedDocument */
class Address
{
    /** @ODM\Field(type="string") */
    protected $street;

    /** @ODM\Field(type="string") */
    protected $city;

    /** @ODM\Field(type="string") */
    protected $state;

    /** @ODM\Field(type="string") */
    protected $postalCode;

    public function getStreet()
    {
        return $this->street;
    }

    public function setStreet($street)
    {
        $this->street = $street;
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

    public function getPostalCode()
    {
        return $this->postalCode;
    }

    public function setPostalCode($postalCode)
    {
        $this->postalCode = $postalCode;
    }
}
