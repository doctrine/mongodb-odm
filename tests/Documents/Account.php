<?php

declare(strict_types=1);

namespace Documents;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/** @ODM\Document */
class Account
{
    /**
     * @ODM\Id
     *
     * @var string|null
     */
    private $id;

    /**
     * @ODM\Field
     *
     * @var string|null
     */
    private $name;

    /**
     * @ODM\ReferenceOne(storeAs="dbRefWithDb")
     *
     * @var User|CustomUser|null
     */
    protected $user;

    /**
     * @ODM\ReferenceOne(storeAs="dbRef")
     *
     * @var User|null
     */
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
