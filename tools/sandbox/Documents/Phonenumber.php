<?php

namespace Documents;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/** @ODM\EmbeddedDocument */
class Phonenumber
{
    /**
     * @ODM\String
     * @var string
     */
    protected $phonenumber;

    /**
     * @param string|null $phonenumber
     */
    public function __construct($phonenumber = null)
    {
        $this->phonenumber = $phonenumber;
    }

    /**
     * @param string|null $phonenumber
     */
    public function setPhonenumber($phonenumber)
    {
        $this->phonenumber = $phonenumber;
    }

    /**
     * @return string|ull
     */
    public function getPhonenumber()
    {
        return $this->phonenumber;
    }

    /**
     * @return string Get the phoneNumber as a string
     */
    public function __toString()
    {
        return (string)$this->phonenumber;
    }
}
