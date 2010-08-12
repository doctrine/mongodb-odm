<?php

namespace Documents;

use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;

/** @Document(db="doctrine_odm_tests", collection="accounts") */
class Account
{
    /** @Id */
    private $id;

    /** @Field */
    private $name;

    /** @ReferenceOne */
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