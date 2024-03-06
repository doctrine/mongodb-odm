<?php

declare(strict_types=1);

namespace Documents;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

#[ODM\Document(collection: 'custom_users')]
class CustomUser
{
    /** @var string|null */
    #[ODM\Id(strategy: 'none')]
    protected $id;

    /** @var string|null */
    #[ODM\Field(type: 'string')]
    protected $username;

    /** @var string|null */
    #[ODM\Field(type: 'string')]
    protected $password;

    /** @var Account|null */
    #[ODM\ReferenceOne(targetDocument: Account::class, cascade: ['all'])]
    protected $account;

    public function getId(): ?string
    {
        return $this->id;
    }

    public function setId(string $id): void
    {
        $this->id = $id;
    }

    public function setUsername(string $username): void
    {
        $this->username = $username;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setPassword(string $password): void
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
