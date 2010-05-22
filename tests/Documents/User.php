<?php

namespace Documents;

use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;

/**
 * @Document(collection="users")
 * @InheritanceType("COLLECTION_PER_CLASS")
 * @DiscriminatorField(fieldName="type")
 * @DiscriminatorMap({"special"="Documents\SpecialUser"})
 */
class User extends BaseDocument
{
    /** @Id */
    protected $id;

    /** @Field(type="string") */
    protected $username;

    /** @BinMD5 */
    protected $password;

    /** @Date */
    protected $createdAt;

    /** @EmbedOne(targetDocument="Address") */
    protected $address;

    /** @ReferenceOne(targetDocument="Profile", cascade={"all"}) */
    protected $profile;

    /** @EmbedMany(targetDocument="Phonenumber") */
    protected $phonenumbers;

    /** @ReferenceMany(targetDocument="Group", cascade={"all"}) */
    protected $groups;

    /** @ReferenceOne(targetDocument="Account", cascade={"all"}) */
    protected $account;

    /** @Int */
    protected $hits = 0;

    /** @String */
    protected $nullTest;

    public function __construct()
    {
        $this->phonenumbers = new \Doctrine\Common\Collections\ArrayCollection();
        $this->groups = array();
        $this->createdAt = new \DateTime();
    }

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
        $this->password = $password;
    }

    public function getPassword()
    {
        return $this->password;
    }

    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;
    }

    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    public function getAddress()
    {
        return $this->address;
    }

    public function setAddress(Address $address)
    {
        $this->address = $address;
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

    public function getGroups()
    {
        return $this->groups;
    }

    public function addGroup(Group $group)
    {
        $this->groups[] = $group;
    }

    public function getHits()
    {
        return $this->hits;
    }

    public function setHits($hits)
    {
        $this->hits = $hits;
    }
}