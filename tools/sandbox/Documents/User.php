<?php

namespace Documents;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/** @ODM\Document(collection="users") */
class User
{
    /** @ODM\Id */
    protected $id;

    /** @ODM\String */
    private $username;

    /** @ODM\Bin(type="bin_md5") */
    protected $password;

    /** @ODM\EmbedOne(targetDocument="Address") */
    protected $address;

    /** @ODM\ReferenceOne(targetDocument="Account") */
    protected $account;

    /** @ODM\EmbedMany(targetDocument="Phonenumber") */
    protected $phonenumbers = array();

    public function getId()
    {
        return $this->id;
    }

    public function setUsername($username)
    {
        $this->username = $username;
    }

    public function getUsername()
    {
        return $this->username;
    }

    public function setPassword($password)
    {
        $this->password = md5($password);
    }

    public function checkPassword($password)
    {
        return $this->password === md5($password) ? true : false;
    }

    public function setAddress(Address $address)
    {
        $this->address = $address;
    }

    public function getAddress()
    {
        return $this->address;
    }

    public function setAccount(Account $account)
    {
        $this->account = $account;
    }

    public function getAccount()
    {
        return $this->account;
    }

    public function addPhonenumber(Phonenumber $phonenumber)
    {
        $this->phonenumbers[] = $phonenumber;
    }

    public function getPhonenumbers()
    {
        return $this->phonenumbers;
    }

    public function __toString()
    {
        return $this->username;
    }
}
