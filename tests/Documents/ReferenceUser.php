<?php

namespace Documents;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * A document to test the different "storeAs" values
 *
 * @ODM\Document
 */
class ReferenceUser
{
    /**
     * @ODM\Id
     */
    public $id;

    /**
     * @ODM\ReferenceOne(targetDocument="Documents\User", storeAs="id")
     *
     * @var User
     */
    public $user;

    /**
     * @ODM\ReferenceMany(targetDocument="Documents\User", storeAs="id")
     *
     * @var User[]
     */
    public $users = array();

    /**
     * @ODM\ReferenceOne(targetDocument="Documents\User", storeAs="dbRef")
     *
     * @var User
     */
    public $parentUser;

    /**
     * @ODM\ReferenceMany(targetDocument="Documents\User", storeAs="dbRef")
     *
     * @var User[]
     */
    public $parentUsers = array();

    /**
     * @ODM\ReferenceOne(targetDocument="Documents\User", storeAs="dbRefWithDb")
     *
     * @var User
     */
    public $otherUser;

    /**
     * @ODM\ReferenceMany(targetDocument="Documents\User", storeAs="dbRefWithDb")
     *
     * @var User[]
     */
    public $otherUsers = array();

    /**
     * @ODM\Field(type="string")
     *
     * @var string
     */
    public $name;

    /**
     * @param User $user
     */
    public function setUser(User $user)
    {
        $this->user = $user;
    }

    /**
     * @return User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @param User $user
     */
    public function addUser(User $user)
    {
        $this->users[] = $user;
    }

    /**
     * @return User[]
     */
    public function getUsers()
    {
        return $this->users;
    }

    /**
     * @param User $parentUser
     */
    public function setParentUser(User $parentUser)
    {
        $this->parentUser = $parentUser;
    }

    /**
     * @return User
     */
    public function getParentUser()
    {
        return $this->parentUser;
    }

    /**
     * @param User $parentUser
     */
    public function addParentUser(User $parentUser)
    {
        $this->parentUsers[] = $parentUser;
    }

    /**
     * @return User[]
     */
    public function getParentUsers()
    {
        return $this->parentUsers;
    }

    /**
     * @param User $otherUser
     */
    public function setOtherUser(User $otherUser)
    {
        $this->otherUser = $otherUser;
    }

    /**
     * @return User
     */
    public function getOtherUser()
    {
        return $this->otherUser;
    }

    /**
     * @param User $otherUser
     */
    public function addOtherUser(User $otherUser)
    {
        $this->otherUsers[] = $otherUser;
    }

    /**
     * @return User[]
     */
    public function getOtherUsers()
    {
        return $this->otherUsers;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }
}
