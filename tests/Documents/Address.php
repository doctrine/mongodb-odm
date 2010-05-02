<?php

namespace Documents;

use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;

class Address
{
    public $address;
    public $city;
    public $state;
    public $zipcode;
}