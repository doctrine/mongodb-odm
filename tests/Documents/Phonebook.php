<?php

declare(strict_types=1);

namespace Documents;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/** @ODM\EmbeddedDocument */
class Phonebook
{
    /**
     * @ODM\Field(type="string")
     *
     * @var string
     */
    private $title;

    /**
     * @ODM\EmbedMany(targetDocument=Phonenumber::class)
     *
     * @var Collection<int, Phonenumber>
     */
    private $phonenumbers;

    public function __construct(string $title)
    {
        $this->title        = $title;
        $this->phonenumbers = new ArrayCollection();
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function addPhonenumber(Phonenumber $phonenumber): void
    {
        $this->phonenumbers->add($phonenumber);
    }

    /** @return Collection<int, Phonenumber> */
    public function getPhonenumbers(): Collection
    {
        return $this->phonenumbers;
    }

    public function removePhonenumber(Phonenumber $phonenumber): void
    {
        $this->phonenumbers->removeElement($phonenumber);
    }
}
