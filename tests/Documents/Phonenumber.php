<?php

namespace Documents;

use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;

class Phonenumber
{
    public $number;

    public function __construct($number)
    {
        $this->number = $number;
    }
}