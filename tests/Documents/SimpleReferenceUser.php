<?php

declare(strict_types=1);

namespace Documents;

use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/** @ODM\Document */
class SimpleReferenceUser
{
    /**
     * @ODM\Id
     *
     * @var string|null
     */
    public $id;

    /**
     * @ODM\ReferenceOne(targetDocument=User::class, storeAs="id", name="userId", inversedBy="simpleReferenceManyInverse") @ODM\Index
     *
     * @var User|null
     */
    public $user;

    /**
     * @ODM\ReferenceMany(targetDocument=User::class, storeAs="id")
     *
     * @var Collection<int, User>|array<User>
     */
    public $users = [];

    /**
     * @ODM\Field(type="string")
     *
     * @var string|null
     */
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
