<?php

namespace Documents;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/** @ODM\Document */
class Account
{
    /** @ODM\Id */
    private $id;

    /** @ODM\Field */
    private $name;

    /** @ODM\ReferenceOne */
    protected $user;

    /** @ODM\ReferenceOne(storeAs="dbRef") */
    protected $userDbRef;

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

    public function setUserDbRef($userDbRef)
    {
        $this->userDbRef = $userDbRef;
    }

    public function getUserDbRef()
    {
        return $this->userDbRef;
    }
}
