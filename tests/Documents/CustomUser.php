<?php

declare(strict_types=1);

namespace Documents;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/** @ODM\Document(collection="custom_users") */
class CustomUser
{
    /**
     * @ODM\Id(strategy="none")
     *
     * @var string|null
     */
    protected $id;

    /**
     * @ODM\Field(type="string")
     *
     * @var string|null
     */
    protected $username;

    /**
     * @ODM\Field(type="string")
     *
     * @var string|null
     */
    protected $password;

    /**
     * @ODM\ReferenceOne(targetDocument=Account::class, cascade={"all"})
     *
     * @var Account|null
     */
    protected $account;

    public function getId(): ?string
    {
        return $this->id;
    }

    public function setId($id): void
    {
        $this->id = $id;
    }

    public function setUsername($username): void
    {
        $this->username = $username;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setPassword($password): void
    {
        $this->password = $password;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setAccount(Account $account): void
    {
        $this->account = $account;
        $this->account->setUser($this);
    }

    public function getAccount(): ?Account
    {
        return $this->account;
    }
}
