<?php

namespace Documents;

/** @Document(collection="users") */
class User
{
    /** @Id */
    private $id;

    /** @String */
    private $username;

    /** @BinDataMD5 */
    private $password;

    /** @EmbedOne(targetDocument="Address") */
    private $address;

    /** @ReferenceOne(targetDocument="Account") */
    private $account;

    /** @EmbedMany(targetDocument="Phonenumber") */
    private $phonenumbers = array();

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