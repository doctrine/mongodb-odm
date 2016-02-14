<?php

namespace Documents;

use Doctrine\Common\Collections\ArrayCollection;
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

    /** @ODM\Field(type="bin_md5") */
    protected $password;

    /** @ODM\Field(type="date") */
    protected $createdAt;

    /** @ODM\EmbedOne(targetDocument="Address", nullable=true) */
    protected $address;

    /** @ODM\ReferenceOne(targetDocument="Profile", cascade={"all"}) */
    protected $profile;

    /** @ODM\ReferenceOne(targetDocument="ProfileNotify", cascade={"all"}) */
    protected $profileNotify;

    /** @ODM\EmbedMany(targetDocument="Phonenumber") */
    protected $phonenumbers;

    /** @ODM\EmbedMany(targetDocument="Phonebook") */
    protected $phonebooks;

    /** @ODM\ReferenceMany(targetDocument="Group", cascade={"all"}) */
    protected $groups;

    /** @ODM\ReferenceMany(targetDocument="Group", simple=true, cascade={"all"}) */
    protected $groupsSimple;

    /** @ODM\ReferenceMany(targetDocument="Group", cascade={"all"}, strategy="addToSet") */
    protected $uniqueGroups;

    /** @ODM\ReferenceMany(targetDocument="Group", name="groups", sort={"name"="asc"}, strategy="setArray") */
    protected $sortedAscGroups;

    /** @ODM\ReferenceMany(targetDocument="Group", name="groups", sort={"name"="desc"}, strategy="setArray") */
    protected $sortedDescGroups;

    /** @ODM\ReferenceOne(targetDocument="Account", cascade={"all"}) */
    protected $account;

    /** @ODM\ReferenceOne(targetDocument="Account", simple=true, cascade={"all"}) */
    protected $accountSimple;

    /** @ODM\Field(type="int") */
    protected $hits = 0;

    /** @ODM\Field(type="string") */
    protected $nullTest;

    /** @ODM\Field(type="int", strategy="increment") */
    protected $count;

    /** @ODM\Field(type="float", strategy="increment") */
    protected $floatCount;

    /** @ODM\ReferenceMany(targetDocument="BlogPost", mappedBy="user", nullable=true) */
    protected $posts;

    /** @ODM\ReferenceOne(targetDocument="Documents\SimpleReferenceUser", mappedBy="user") */
    protected $simpleReferenceOneInverse;

    /** @ODM\ReferenceMany(targetDocument="Documents\SimpleReferenceUser", mappedBy="users") */
    protected $simpleReferenceManyInverse;

    /** @ODM\Field(type="collection") */
    private $logs = array();

    public function __construct()
    {
        $this->phonebooks = new ArrayCollection();
        $this->phonenumbers = new ArrayCollection();
        $this->groups = new ArrayCollection();
        $this->groupsSimple = new ArrayCollection();
        $this->sortedGroups = new ArrayCollection();
        $this->sortedGroupsAsc = new ArrayCollection();
        $this->posts = new ArrayCollection();
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

    public function setProfileNotify(ProfileNotify $profile)
    {
        $this->profileNotify = $profile;
    }

    public function getProfileNotify()
    {
        return $this->profileNotify;
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

    public function setAccountSimple(Account $account)
    {
        $this->accountSimple = $account;
        $this->accountSimple->setUser($this);
    }

    public function getAccountSimple()
    {
        return $this->accountSimple;
    }

    public function getPhonenumbers()
    {
        return $this->phonenumbers;
    }

    public function addPhonenumber(Phonenumber $phonenumber)
    {
        $this->phonenumbers[] = $phonenumber;
    }

    public function getSortedAscGroups()
    {
        return $this->sortedAscGroups;
    }

    public function getSortedDescGroups()
    {
        return $this->sortedDescGroups;
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

    public function addGroupSimple(Group $group)
    {
        $this->groupsSimple[] = $group;
    }

    public function getUniqueGroups()
    {
        return $this->uniqueGroups;
    }

    public function setUniqueGroups($groups)
    {
        $this->uniqueGroups = $groups;
    }

    public function addUniqueGroup(Group $group)
    {
        $this->uniqueGroups[] = $group;
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

    public function getFloatCount()
    {
        return $this->floatCount;
    }

    public function setFloatCount($floatCount)
    {
        $this->floatCount = $floatCount;
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
            $this->count += $num;
        }
    }

    public function incrementFloatCount($num = null)
    {
        if ($num === null) {
            $this->floatCount++;
        } else {
            $this->floatCount += $num;
        }
    }

    public function setPosts($posts)
    {
        $this->posts = $posts;
    }

    public function addPost(BlogPost $post)
    {
        $this->posts[] = $post;
    }

    public function removePost($id)
    {
        foreach ($this->posts as $key => $post) {
            if ($post->getId() === $id) {
                unset($this->posts[$key]);
                return true;
            }
        }
        return false;
    }

    public function getPosts()
    {
        return $this->posts;
    }

    public function setPhonenumbers($phonenumbers)
    {
        $this->phonenumbers = $phonenumbers;
    }

    public function addPhonebook(Phonebook $phonebook)
    {
        $this->phonebooks->add($phonebook);
    }

    public function getPhonebooks()
    {
        return $this->phonebooks;
    }

    public function removePhonebook(Phonebook $phonebook)
    {
        $this->phonebooks->removeElement($phonebook);
    }
}
