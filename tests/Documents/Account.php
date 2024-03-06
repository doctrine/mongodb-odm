<?php

declare(strict_types=1);

namespace Documents;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

#[ODM\Document]
class Account
{
    /** @var string|null */
    #[ODM\Id]
    private $id;

    /** @var string|null */
    #[ODM\Field]
    private $name;

    /** @var User|CustomUser|null */
    #[ODM\ReferenceOne(storeAs: 'dbRefWithDb')]
    protected $user;

    /** @var User|null */
    #[ODM\ReferenceOne(storeAs: 'dbRef')]
    protected $userDbRef;

    public function getId(): ?string
    {
        return $this->id;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    /** @param User|CustomUser $user */
    public function setUser($user): void
    {
        $this->user = $user;
    }

    /** @return CustomUser|User|null */
    public function getUser()
    {
        return $this->user;
    }

    public function setUserDbRef(User $userDbRef): void
    {
        $this->userDbRef = $userDbRef;
    }

    public function getUserDbRef(): ?User
    {
        return $this->userDbRef;
    }
}
