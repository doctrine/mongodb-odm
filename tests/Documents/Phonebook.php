<?php

namespace Documents;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/** @ODM\EmbeddedDocument */
class Phonebook
{
    /** @ODM\Field(type="string") */
    private $title;

    /** @ODM\EmbedMany(targetDocument="Phonenumber") */
    private $phonenumbers;

    public function __construct($title)
    {
        $this->title = $title;
        $this->phonenumbers = new ArrayCollection();
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function setTitle($title)
    {
        $this->title = $title;
    }

    public function addPhonenumber(Phonenumber $phonenumber)
    {
        $this->phonenumbers->add($phonenumber);
    }

    public function getPhonenumbers()
    {
        return $this->phonenumbers;
    }

    public function removePhonenumber(Phonenumber $phonenumber)
    {
        $this->phonenumbers->removeElement($phonenumber);
    }
}
