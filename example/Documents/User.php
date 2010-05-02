<?php

namespace Documents;

/** @Document(indexes={
  *   @Index(keys={"username"="desc"}, options={"unique"=true})
  * })
  * @InheritanceType("SINGLE_COLLECTION")
  * @DiscriminatorField(fieldName="type")
  * @DiscriminatorMap({"moderator"="Documents\Moderator", "admin"="Documents\Admin"})
  */
class User
{
    /** @Id */
    protected $id;

    /** @Field */
    protected $username;

    /** @Field */
    protected $password;

    /** @EmbedMany(targetDocument="Documents\Phonenumber") */
    protected $phonenumbers = array();

    /** @EmbedMany(targetDocument="Documents\Address") */
    protected $addresses = array();

    /** @ReferenceOne(targetDocument="Documents\Profile", cascadeDelete="true") */
    protected $profile;

    /** @ReferenceOne(targetDocument="Documents\Account", cascadeDelete="true") */
    protected $account;

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