<?php

declare(strict_types=1);

namespace Documents;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * @ODM\Document
 */
#[ODM\Document]
class CmsUser
{
    /** @ODM\Id */
    #[ODM\Id]
    public $id;

    /** @ODM\Field(type="string") */
    #[ODM\Field(type: 'string')]
    public $status;

    /** @ODM\Field(type="string") */
    #[ODM\Field(type: 'string')]
    public $username;

    /** @ODM\Field(type="string") */
    #[ODM\Field(type: 'string')]
    public $name;

    /** @ODM\ReferenceMany(targetDocument=CmsPhonenumber::class, mappedBy="user", cascade={"persist", "remove", "merge"}) */
    public $phonenumbers;

    /** @ODM\ReferenceMany(targetDocument=CmsArticle::class) */
    public $articles;

    /** @ODM\ReferenceOne(targetDocument=CmsAddress::class, cascade={"persist"}) */
    public $address;

    /** @ODM\ReferenceMany(targetDocument=CmsGroup::class, cascade={"persist", "merge"}) */
    public $groups;

    public function __construct()
    {
        $this->phonenumbers = new ArrayCollection();
        $this->articles     = new ArrayCollection();
        $this->groups       = new ArrayCollection();
    }

    public function getId()
    {
        return $this->id;
    }

    public function getStatus()
    {
        return $this->status;
    }

    public function getUsername()
    {
        return $this->username;
    }

    public function getName()
    {
        return $this->name;
    }

    /**
     * Adds a phonenumber to the user.
     */
    public function addPhonenumber(CmsPhonenumber $phone): void
    {
        $this->phonenumbers[] = $phone;
        $phone->setUser($this);
    }

    public function getPhonenumbers(): Collection
    {
        return $this->phonenumbers;
    }

    public function addArticle(CmsArticle $article): void
    {
        $this->articles[] = $article;
        $article->setAuthor($this);
    }

    public function addGroup(CmsGroup $group): void
    {
        $this->groups[] = $group;
        $group->addUser($this);
    }

    public function getGroups(): Collection
    {
        return $this->groups;
    }

    public function removePhonenumber($index): bool
    {
        if (isset($this->phonenumbers[$index])) {
            $ph = $this->phonenumbers[$index];
            unset($this->phonenumbers[$index]);
            $ph->user = null;

            return true;
        }

        return false;
    }

    public function getAddress()
    {
        return $this->address;
    }

    public function setAddress(CmsAddress $address): void
    {
        if ($this->address === $address) {
            return;
        }

        $this->address = $address;
        $address->setUser($this);
    }
}
