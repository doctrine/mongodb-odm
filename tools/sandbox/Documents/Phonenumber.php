<?php

namespace Documents;

/** @EmbeddedDocument */
class Phonenumber
{
    /** @String */
    private $phonenumber;

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
        return $this->phonenumber;
    }
}