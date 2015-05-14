<?php

namespace Documents;

use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/** @ODM\EmbeddedDocument */
class PhonenumberDemo
{
    /** @ODM\String */
    private $phonenumber;

    public function getPhonenumber()
    {
        return $this->phonenumber;
    }

    public function setPhonenumber($phonenumber)
    {
        $this->phonenumber = $phonenumber;
    }
}