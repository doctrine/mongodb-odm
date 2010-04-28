<?php

namespace Entities;

class Phonenumber
{
    private $id;
    private $phonenumber;

    public function __construct($phonenumber = null)
    {
        $this->phonenumber = $phonenumber;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getPhonenumber()
    {
        return $this->phonenumber;
    }

    public function setPhonenumber($phonenumber)
    {
        $this->phonenumber = $phonenumber;
    }
}