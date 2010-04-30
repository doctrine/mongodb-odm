<?php

namespace Documents;

/** @Document */
class User
{
    /** @Field */
    private $id;

    /** @Field */
    private $username;

    /** @Field */
    private $password;

    /** @Field(embedded="true", targetDocument="Documents\Phonenumber", type="many", cascadeDelete="true") */
    private $phonenumbers = array();

    /** @Field(embedded="true", targetDocument="Documents\Address", type="many") */
    private $addresses = array();

    /** @Field(reference="true", targetDocument="Documents\Profile", type="one") */
    private $profile;

    /** @Field(reference="true", targetDocument="Documents\Account", cascadeDelete="true", type="one") */
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