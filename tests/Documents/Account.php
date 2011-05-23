<?php

namespace Documents;

use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/** @ODM\Document(db="doctrine_odm_tests", collection="accounts") */
class Account
{
    /** @ODM\Id */
    private $id;

    /** @ODM\Field */
    private $name;

    /** @ODM\ReferenceOne */
    protected $user;

    public function getId()
    {
        return $this->id;
    }

    public function setName($name)
    {
        $this->name = $name;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setUser($user)
    {
        $this->user = $user;
    }

    public function getUser()
    {
        return $this->user;
    }
}