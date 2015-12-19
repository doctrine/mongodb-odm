<?php

namespace Documents;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/** @ODM\EmbeddedDocument */
class Phonenumber
{
    /** @ODM\Field(type="string") */
    protected $phonenumber;

    public function __construct($phonenumber = null)
    {
        $this->phonenumber = $phonenumber;
    }

    public function setPhonenumber($phonenumber)
    {
        $this->phonenumber = $phonenumber;
    }

    public function getPhonenumber()
    {
        return $this->phonenumber;
    }

    public function __toString()
    {
        return (string) $this->phonenumber;
    }
}
