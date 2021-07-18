<?php

declare(strict_types=1);

namespace Documents;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * A document to test the different "storeAs" values
 *
 * @ODM\Document
 */
class ReferenceUser
{
    /** @ODM\Id */
    public $id;

    /**
     * @ODM\ReferenceOne(targetDocument=User::class, storeAs="id")
     *
     * @var User
     */
    public $user;

    /**
     * @ODM\ReferenceMany(targetDocument=User::class, storeAs="id")
     *
     * @var User[]
     */
    public $users = [];

    /**
     * @ODM\ReferenceOne(targetDocument=User::class, storeAs="dbRef")
     *
     * @var User
     */
    public $parentUser;

    /**
     * @ODM\ReferenceMany(targetDocument=User::class, storeAs="dbRef")
     *
     * @var User[]
     */
    public $parentUsers = [];

    /**
     * @ODM\ReferenceOne(targetDocument=User::class, storeAs="dbRefWithDb")
     *
     * @var User
     */
    public $otherUser;

    /**
     * @ODM\ReferenceMany(targetDocument=User::class, storeAs="dbRefWithDb")
     *
     * @var User[]
     */
    public $otherUsers = [];

    /**
     * @ODM\ReferenceOne(targetDocument=User::class, storeAs="ref")
     *
     * @var User
     */
    public $referencedUser;

    /**
     * @ODM\ReferenceMany(targetDocument=User::class, storeAs="ref")
     *
     * @var User[]
     */
    public $referencedUsers = [];

    /**
     * @ODM\EmbedMany(targetDocument=Documents\IndirectlyReferencedUser::class)
     *
     * @var IndirectlyReferencedUser[]
     */
    public $indirectlyReferencedUsers = [];

    /**
     * @ODM\Field(type="string")
     *
     * @var string
     */
    public $name;

    public function setUser(User $user): void
    {
        $this->user = $user;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function addUser(User $user): void
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

    public function setParentUser(User $parentUser): void
    {
        $this->parentUser = $parentUser;
    }

    public function getParentUser(): User
    {
        return $this->parentUser;
    }

    public function addParentUser(User $parentUser): void
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

    public function setOtherUser(User $otherUser): void
    {
        $this->otherUser = $otherUser;
    }

    public function getOtherUser(): User
    {
        return $this->otherUser;
    }

    public function addOtherUser(User $otherUser): void
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

    public function setReferencedUser(User $referencedUser): void
    {
        $this->referencedUser = $referencedUser;
    }

    public function getreferencedUser(): User
    {
        return $this->referencedUser;
    }

    public function addReferencedUser(User $referencedUser): void
    {
        $this->referencedUsers[] = $referencedUser;
    }

    /**
     * @return User[]
     */
    public function getReferencedUsers()
    {
        return $this->referencedUsers;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }
}
