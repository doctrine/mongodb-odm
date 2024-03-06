<?php

declare(strict_types=1);

namespace Documents;

use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * A document to test the different "storeAs" values
 */
#[ODM\Document]
class ReferenceUser
{
    /** @var string|null */
    #[ODM\Id]
    public $id;

    /** @var User */
    #[ODM\ReferenceOne(targetDocument: User::class, storeAs: 'id')]
    public $user;

    /** @var Collection<int, User>|array<User> */
    #[ODM\ReferenceMany(targetDocument: User::class, storeAs: 'id')]
    public $users = [];

    /** @var User */
    #[ODM\ReferenceOne(targetDocument: User::class, storeAs: 'dbRef')]
    public $parentUser;

    /** @var Collection<int, User>|array<User> */
    #[ODM\ReferenceMany(targetDocument: User::class, storeAs: 'dbRef')]
    public $parentUsers = [];

    /** @var User */
    #[ODM\ReferenceOne(targetDocument: User::class, storeAs: 'dbRefWithDb')]
    public $otherUser;

    /** @var Collection<int, User>|array<User> */
    #[ODM\ReferenceMany(targetDocument: User::class, storeAs: 'dbRefWithDb')]
    public $otherUsers = [];

    /** @var User */
    #[ODM\ReferenceOne(targetDocument: User::class, storeAs: 'ref')]
    public $referencedUser;

    /** @var Collection<int, User>|array<User> */
    #[ODM\ReferenceMany(targetDocument: User::class, storeAs: 'ref')]
    public $referencedUsers = [];

    /** @var Collection<int, IndirectlyReferencedUser>|array<IndirectlyReferencedUser> */
    #[ODM\EmbedMany(targetDocument: IndirectlyReferencedUser::class)]
    public $indirectlyReferencedUsers = [];

    /** @var string */
    #[ODM\Field(type: 'string')]
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

    /** @return Collection<int, User>|array<User> */
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

    /** @return Collection<int, User>|array<User> */
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

    /** @return Collection<int, User>|array<User> */
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

    /** @return Collection<int, User>|array<User> */
    public function getReferencedUsers()
    {
        return $this->referencedUsers;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }
}
