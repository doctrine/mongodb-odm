<?php

namespace Entities;

use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;

class User
{
    public static function loadMetadata(ClassMetadata $metadata)
    {
        $metadata->mapManyEmbedded(array(
            'fieldName' => 'addresses',
            'targetEntity' => 'Entities\Address'
        ));
        $metadata->mapOneEmbedded(array(
            'fieldName' => 'profile',
            'targetEntity' => 'Entities\Profile'
        ));
        $metadata->mapOneAssociation(array(
            'fieldName' => 'account',
            'targetEntity' => 'Entities\Account',
            'cascadeDelete' => true
        ));
        $metadata->mapManyAssociation(array(
            'fieldName' => 'phonenumbers',
            'targetEntity' => 'Entities\Phonenumber',
            'cascadeDelete' => true
        ));
    }

    private $id;
    private $username;
    private $password;
    private $addresses = array();
    private $profile;
    private $account;
    private $phonenumbers = array();

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