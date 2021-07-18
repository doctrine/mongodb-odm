<?php

declare(strict_types=1);

namespace Documents;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/** @ODM\EmbeddedDocument */
class Phonebook
{
    /** @ODM\Field(type="string") */
    private $title;

    /** @ODM\EmbedMany(targetDocument=Phonenumber::class) */
    private $phonenumbers;

    public function __construct($title)
    {
        $this->title        = $title;
        $this->phonenumbers = new ArrayCollection();
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function setTitle($title): void
    {
        $this->title = $title;
    }

    public function addPhonenumber(Phonenumber $phonenumber): void
    {
        $this->phonenumbers->add($phonenumber);
    }

    public function getPhonenumbers(): Collection
    {
        return $this->phonenumbers;
    }

    public function removePhonenumber(Phonenumber $phonenumber): void
    {
        $this->phonenumbers->removeElement($phonenumber);
    }
}
