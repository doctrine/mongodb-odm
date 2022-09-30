<?php

declare(strict_types=1);

namespace TestDocuments;

use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\Tests\Mapping\Phonenumber;
use Documents\Account;
use Documents\Address;
use Documents\Group;
use Documents\Profile;

class User
{
    /** @var string|null */
    protected $id;

    /** @var string|null */
    protected $username;

    /** @var string|null */
    protected $password;

    /** @var DateTime */
    protected $createdAt;

    /** @var Address|null */
    protected $address;

    /** @var Profile|null */
    protected $profile;

    /** @var Collection<int, Phonenumber> */
    protected $phonenumbers;

    /** @var Collection<int, Group>|Group[] */
    protected $groups = [];

    /** @var Account|null */
    protected $account;

    /** @var string[] */
    protected $tags = [];

    /** @var EmbeddedDocument|null */
    protected $test;

    public function __construct()
    {
        $this->phonenumbers = new ArrayCollection();
        $this->createdAt    = new DateTime();
    }

    public function getId(): ?string
    {
        return $this->id;
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

    public function setCreatedAt(DateTime $createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    public function getCreatedAt(): DateTime
    {
        return $this->createdAt;
    }

    public function getAddress(): ?Address
    {
        return $this->address;
    }

    public function setAddress(Address $address): void
    {
        $this->address = $address;
    }

    public function setProfile(Profile $profile): void
    {
        $this->profile = $profile;
    }

    public function getProfile(): ?Profile
    {
        return $this->profile;
    }

    public function setAccount(Account $account): void
    {
        $this->account = $account;
    }

    public function getAccount(): ?Account
    {
        return $this->account;
    }

    public function getPhonenumbers(): Collection
    {
        return $this->phonenumbers;
    }

    public function addPhonenumber(Phonenumber $phonenumber): void
    {
        $this->phonenumbers[] = $phonenumber;
    }

    public function getGroups(): Collection
    {
        return $this->groups;
    }

    public function addGroup(Group $group): void
    {
        $this->groups[] = $group;
    }
}
