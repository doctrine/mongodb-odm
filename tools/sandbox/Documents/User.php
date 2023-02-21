<?php

declare(strict_types=1);

namespace Documents;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

use function md5;

/** @ODM\Document(collection="users") */
class User
{
    /**
     * @ODM\Id
     *
     * @var string|null
     */
    protected $id;

    /**
     * @ODM\Field(type="string")
     *
     * @var string|null
     */
    private $username;

    /**
     * @ODM\Field(type="string")
     *
     * @var string|null
     */
    protected $password;

    /**
     * @ODM\EmbedOne(targetDocument=Address::class)
     *
     * @var Address|null
     */
    protected $address;

    /**
     * @ODM\ReferenceOne(targetDocument=Account::class)
     *
     * @var Account|null
     */
    protected $account;

    /**
     * @ODM\EmbedMany(targetDocument=Phonenumber::class)
     *
     * @var Collection<int, Phonenumber>
     */
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
