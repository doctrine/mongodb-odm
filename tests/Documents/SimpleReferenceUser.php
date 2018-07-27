<?php

declare(strict_types=1);

namespace Documents;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * @ODM\Document
 */
class SimpleReferenceUser
{
    /** @ODM\Id */
    public $id;

    /** @ODM\ReferenceOne(targetDocument=User::class, storeAs="id", name="userId") @ODM\Index */
    public $user;

    /** @ODM\ReferenceMany(targetDocument=User::class, storeAs="id") */
    public $users = [];

    /** @ODM\Field(type="string") */
    public $name;

    public function setUser($user)
    {
        $this->user = $user;
    }

    public function getUser()
    {
        return $this->user;
    }

    public function addUser($user)
    {
        $this->users[] = $user;
    }

    public function getUsers()
    {
        return $this->users;
    }

    public function setName($name)
    {
        $this->name = $name;
    }

    public function getName()
    {
        return $this->name;
    }
}
