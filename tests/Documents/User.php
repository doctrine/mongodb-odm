<?php

namespace Documents;

use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * @ODM\Document(collection="users")
 * @ODM\InheritanceType("COLLECTION_PER_CLASS")
 */
class User extends BaseDocument
{
    /** @ODM\Id */
    protected $id;

    /** @ODM\Field(type="string") */
    protected $username;

    /** @ODM\Bin(type="bin_md5") */
    protected $password;

    /** @ODM\Date */
    protected $createdAt;

    /** @ODM\EmbedOne(targetDocument="Address", nullable=true) */
    protected $address;

    /** @ODM\ReferenceOne(targetDocument="Profile", cascade={"all"}) */
    protected $profile;

    /** @ODM\EmbedMany(targetDocument="Phonenumber") */
    protected $phonenumbers;

    /** @ODM\ReferenceMany(targetDocument="Group", cascade={"all"}) */
    protected $groups;

    /** @ODM\ReferenceOne(targetDocument="Account", cascade={"all"}) */
    protected $account;

    /** @ODM\ReferenceMany(targetDocument="Account", cascade={"all"}) */
    protected $accounts;

    /** @ODM\Int */
    protected $hits = 0;

    /** @ODM\String */
    protected $nullTest;

    /** @ODM\Increment */
    protected $count = 0;

    /** @ODM\ReferenceOne(targetDocument="Documents\SimpleReferenceUser", mappedBy="user") */
    protected $simpleReferenceOneInverse;

    /** @ODM\ReferenceMany(targetDocument="Documents\SimpleReferenceUser", mappedBy="users") */
    protected $simpleReferenceManyInverse;

    /** @ODM\Collection */
    private $logs = array();

    public function __construct()
    {
        $this->phonenumbers = new \Doctrine\Common\Collections\ArrayCollection();
        $this->groups = array();
        $this->createdAt = new \DateTime();
    }

    public function setId($id)
    {
        $this->id = $id;
    }

    public function getLogs()
    {
        return $this->logs;
    }

    public function setLogs($logs)
    {
        $this->logs = $logs;
    }

    public function log($log)
    {
        $this->logs[] = $log;
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

    public function setAddress(Address $address = null)
    {
        $this->address = $address;
    }

    public function removeAddress()
    {
        $this->address = null;
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
        $this->account->setUser($this);
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

    public function setGroups($groups)
    {
        $this->groups = $groups;
    }

    public function addGroup(Group $group)
    {
        $this->groups[] = $group;
    }

    public function removeGroup($name)
    {
        foreach ($this->groups as $key => $group) {
            if ($group->getName() === $name) {
                unset($this->groups[$key]);
                return true;
            }
        }
        return false;
    }

    public function getHits()
    {
        return $this->hits;
    }

    public function setHits($hits)
    {
        $this->hits = $hits;
    }

    public function getCount()
    {
        return $this->count;
    }

    public function setCount($count)
    {
        $this->count = $count;
    }

    public function getSimpleReferenceOneInverse()
    {
        return $this->simpleReferenceOneInverse;
    }

    public function getSimpleReferenceManyInverse()
    {
        return $this->simpleReferenceManyInverse;
    }

    public function incrementCount($num = null)
    {
        if ($num === null) {
            $this->count++;
        } else {
            $this->count = $this->count + $num;
        }
    }
}