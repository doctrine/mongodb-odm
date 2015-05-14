<?php

namespace Documents;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * @ODM\Document(collection="usersversioned")
 */
class UserVersioned extends BaseDocument
{
    /** @ODM\Id */
    protected $id;

    /** @ODM\Version @ODM\Int */
    public $version;

    /** @ODM\Field(type="string") */
    protected $username;

    /** @ODM\EmbedMany(strategy="set",targetDocument="Phonenumber") */
    protected $phonenumbers;

    public function __construct()
    {
        $this->phonenumbers = new ArrayCollection();
    }
    public function getId()
    {
        return $this->id;
    }

    public function getPhonenumbers()
    {
        return $this->phonenumbers;
    }

    public function addPhonenumber(Phonenumber $phonenumber)
    {
        $this->phonenumbers[] = $phonenumber;
    }

    public function getUsername()
    {
        return $this->username;
    }

    public function setUsername($username)
    {
        return $this->username = $username;
    }
}
