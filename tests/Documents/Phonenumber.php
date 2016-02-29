<?php

namespace Documents;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/** @ODM\EmbeddedDocument */
class Phonenumber
{
    /** @ODM\Field(type="string") */
    private $phonenumber;

    /** @ODM\ReferenceOne(targetDocument="User", cascade={"persist"}) */
    private $lastCalledBy;

    public function __construct($phonenumber = null, User $lastCalledBy = null)
    {
        $this->phonenumber = $phonenumber;
        $this->lastCalledBy = $lastCalledBy;
    }

    public function getPhonenumber()
    {
        return $this->phonenumber;
    }

    public function setPhonenumber($phonenumber)
    {
        $this->phonenumber = $phonenumber;
    }

    public function getLastCalledBy()
    {
        return $this->lastCalledBy;
    }

    public function setLastCalledBy(User $lastCalledBy = null)
    {
        $this->lastCalledBy = $lastCalledBy;
    }
}
