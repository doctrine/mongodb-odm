<?php

namespace Documents;

/** @Document */
class User
{
    /** @Id */
    private $id;

    /** @Field */
    private $username;

    /** @Field */
    private $password;

    /** @EmbedMany(targetDocument="Documents\Phonenumber") */
    private $phonenumbers = array();

    /** @EmbedMany(targetDocument="Documents\Address") */
    private $addresses = array();

    /** @ReferenceOne(targetDocument="Documents\Profile", cascadeDelete="true") */
    private $profile;

    /** @ReferenceOne(targetDocument="Documents\Account", cascadeDelete="true") */
    private $account;

    public function getId()
    {
        return $this->id;
    }

    public function getUsername()
    {
        return $this->username;
    }

    public function setUsername($username)
    {
        $this->username = $username;
    }

    public function getPassword()
    {
        return $this->password;
    }

    public function setPassword($password)
    {
        $this->password = md5($password);
    }

    public function getAddresses()
    {
        return $this->addresses;
    }

    public function addAddress(Address $address)
    {
        $this->addresses[] = $address;
    }

    public function setProfile(Profile $profile)
    {
        $this->profile = $profile;
    }

    public function getProfile()
    {
        return $this->profile;
    }

    public function setAccount(Account $account)
    {
        $this->account = $account;
    }

    public function getAccount()
    {
        return $this->account;
    }

    public function getPhonenumbers()
    {
        return $this->phonenumbers;
    }

    public function addPhonenumber(Phonenumber $phonenumber)
    {
        $this->phonenumbers[] = $phonenumber;
    }
}