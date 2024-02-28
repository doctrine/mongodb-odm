<?php

declare(strict_types=1);

namespace Documents;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Types\Type;

use function md5;

#[ODM\Document(collection: 'users')]
class User
{
    #[ODM\Id]
    protected ?string $id;

    #[ODM\Field(type: Type::STRING)]
    private ?string $username;

    #[ODM\Field(type: Type::STRING)]
    protected ?string $password;

    #[ODM\EmbedOne(targetDocument: Address::class)]
    protected ?Address $address;

    #[ODM\ReferenceOne(targetDocument: Account::class)]
    protected ?Account $account;

    /** @var Collection<int, Phonenumber> */
    #[ODM\EmbedMany(targetDocument: Phonenumber::class)]
    protected $phonenumbers;

    public function __construct()
    {
        $this->phonenumbers = new ArrayCollection();
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function setUsername(string $username)
    {
        $this->username = $username;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setPassword(string $password): void
    {
        $this->password = md5($password);
    }

    public function checkPassword(string $password): bool
    {
        return $this->password === md5($password);
    }

    public function setAddress(Address $address): void
    {
        $this->address = $address;
    }

    public function getAddress(): ?Address
    {
        return $this->address;
    }

    public function setAccount(Account $account): void
    {
        $this->account = $account;
    }

    public function getAccount(): ?Account
    {
        return $this->account;
    }

    public function addPhonenumber(Phonenumber $phonenumber): void
    {
        $this->phonenumbers[] = $phonenumber;
    }

    /** @return Collection<int, Phonenumber> */
    public function getPhonenumbers(): Collection
    {
        return $this->phonenumbers;
    }

    public function __toString(): string
    {
        return (string) $this->username;
    }
}
