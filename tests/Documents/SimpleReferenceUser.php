<?php

declare(strict_types=1);

namespace Documents;

use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

#[ODM\Document]
class SimpleReferenceUser
{
    /** @var string|null */
    #[ODM\Id]
    public $id;

    /** @var User|null */
    #[ODM\ReferenceOne(targetDocument: User::class, storeAs: 'id', name: 'userId', inversedBy: 'simpleReferenceManyInverse')]
    #[ODM\Index]
    public $user;

    /** @var Collection<int, User>|array<User> */
    #[ODM\ReferenceMany(targetDocument: User::class, storeAs: 'id')]
    public $users = [];

    /** @var string|null */
    #[ODM\Field(type: 'string')]
    public $name;

    public function setUser(User $user): void
    {
        $this->user = $user;
    }

    public function getUser(): ?User
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

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getName(): ?string
    {
        return $this->name;
    }
}
