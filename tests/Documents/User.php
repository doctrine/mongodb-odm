<?php

declare(strict_types=1);

namespace Documents;

use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

use function bcadd;

/**
 * @ODM\Document(collection="users")
 * @ODM\InheritanceType("COLLECTION_PER_CLASS")
 */
class User extends BaseDocument
{
    /**
     * @ODM\Id
     *
     * @var string|null
     */
    protected $id;

    /**
     * @ODM\Field(type="string")
     *
     * @var string|null
     */
    protected $username;

    /**
     * @ODM\Field(type="bin_md5")
     *
     * @var string|null
     */
    protected $password;

    /**
     * @ODM\Field(type="date")
     *
     * @var DateTime
     */
    protected $createdAt;

    /**
     * @ODM\EmbedOne(targetDocument=Address::class)
     *
     * @var Address|null
     */
    protected $address;

    /**
     * @ODM\EmbedOne(targetDocument=Address::class, nullable=true)
     *
     * @var Address|null
     */
    protected $addressNullable;

    /**
     * @ODM\ReferenceOne(targetDocument=Profile::class, cascade={"all"})
     *
     * @var Profile|null
     */
    protected $profile;

    /**
     * @ODM\ReferenceOne(targetDocument=ProfileNotify::class, cascade={"all"})
     *
     * @var ProfileNotify|null
     */
    protected $profileNotify;

    /**
     * @ODM\EmbedMany(targetDocument=Phonenumber::class)
     *
     * @var Collection<int, Phonenumber>
     */
    protected $phonenumbers;

    /**
     * @ODM\EmbedMany(targetDocument=Phonebook::class)
     *
     * @var Collection<int, Phonebook>
     */
    protected $phonebooks;

    /**
     * @ODM\ReferenceMany(targetDocument=Group::class, cascade={"all"})
     *
     * @var Collection<int, Group>
     */
    protected $groups;

    /**
     * @ODM\ReferenceMany(targetDocument=Group::class, storeAs="id", cascade={"all"})
     *
     * @var Collection<int, Group>
     */
    protected $groupsSimple;

    /**
     * @ODM\ReferenceMany(targetDocument=Group::class, cascade={"all"}, strategy="addToSet")
     *
     * @var Collection<int, Group>
     */
    protected $uniqueGroups;

    /**
     * @ODM\ReferenceMany(targetDocument=Group::class, name="groups", notSaved=true, sort={"name"="asc"}, strategy="setArray")
     *
     * @var Collection<int, Group>
     */
    protected $sortedAscGroups;

    /**
     * @ODM\ReferenceMany(targetDocument=Group::class, name="groups", notSaved=true, sort={"name"="desc"}, strategy="setArray")
     *
     * @var Collection<int, Group>
     */
    protected $sortedDescGroups;

    /**
     * @ODM\ReferenceOne(targetDocument=Account::class, cascade={"all"})
     *
     * @var Account|null
     */
    protected $account;

    /**
     * @ODM\ReferenceOne(targetDocument=Account::class, storeAs="id", cascade={"all"})
     *
     * @var Account|null
     */
    protected $accountSimple;

    /**
     * @ODM\Field(type="int")
     *
     * @var int|null
     */
    protected $hits = 0;

    /**
     * @ODM\Field(type="string")
     *
     * @var string|null
     */
    protected $nullTest;

    /**
     * @ODM\Field(type="int", strategy="increment")
     *
     * @var int|null
     */
    protected $count;

    /**
     * @ODM\Field(type="float", strategy="increment")
     *
     * @var float|null
     */
    protected $floatCount;

    /**
     * @ODM\Field(type="decimal128", strategy="increment")
     *
     * @var string|null
     */
    protected $decimal128Count;

    /**
     * @ODM\ReferenceMany(targetDocument=BlogPost::class, mappedBy="user", nullable=true)
     *
     * @var Collection<int, BlogPost>
     */
    protected $posts;

    /**
     * @ODM\ReferenceOne(targetDocument=SimpleReferenceUser::class, mappedBy="user")
     *
     * @var SimpleReferenceUser|null
     */
    protected $simpleReferenceOneInverse;

    /**
     * @ODM\ReferenceMany(targetDocument=SimpleReferenceUser::class, mappedBy="users")
     *
     * @var Collection<int, SimpleReferenceUser>
     */
    protected $simpleReferenceManyInverse;

    /**
     * @ODM\ReferenceOne(targetDocument=ReferenceUser::class, mappedBy="referencedUser")
     *
     * @var ReferenceUser|null
     */
    protected $embeddedReferenceOneInverse;

    /**
     * @ODM\ReferenceMany(targetDocument=ReferenceUser::class, mappedBy="referencedUsers")
     *
     * @var Collection<int, ReferenceUser>
     */
    protected $embeddedReferenceManyInverse;

    /**
     * @ODM\Field(type="collection")
     *
     * @var array<string[]>
     */
    private $logs = [];

    /**
     * @ODM\ReferenceOne(storeAs="dbRefWithDb")
     *
     * @var object|null
     */
    protected $referenceToAnything;

    /**
     * @ODM\ReferenceOne(storeAs="dbRef")
     *
     * @var object|null
     */
    protected $referenceToAnythingWithoutDb;

    public function __construct()
    {
        $this->phonebooks       = new ArrayCollection();
        $this->phonenumbers     = new ArrayCollection();
        $this->groups           = new ArrayCollection();
        $this->groupsSimple     = new ArrayCollection();
        $this->sortedAscGroups  = new ArrayCollection();
        $this->sortedDescGroups = new ArrayCollection();
        $this->posts            = new ArrayCollection();
        $this->createdAt        = new DateTime();
    }

    public function setId($id): void
    {
        $this->id = $id;
    }

    public function getLogs(): array
    {
        return $this->logs;
    }

    public function setLogs($logs): void
    {
        $this->logs = $logs;
    }

    public function log($log): void
    {
        $this->logs[] = $log;
    }

    public function getId()
    {
        return $this->id;
    }

    public function setUsername($username): void
    {
        $this->username = $username;
    }

    public function getUsername()
    {
        return $this->username;
    }

    public function setPassword($password): void
    {
        $this->password = $password;
    }

    public function getPassword()
    {
        return $this->password;
    }

    public function setCreatedAt($createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    public function getCreatedAt(): DateTime
    {
        return $this->createdAt;
    }

    public function getAddress()
    {
        return $this->address;
    }

    public function setAddress(?Address $address = null): void
    {
        $this->address         = $address;
        $this->addressNullable = $address ? clone $address : $address;
    }

    public function removeAddress(): void
    {
        $this->address         = null;
        $this->addressNullable = null;
    }

    public function setProfile(Profile $profile): void
    {
        $this->profile = $profile;
    }

    public function getProfile()
    {
        return $this->profile;
    }

    public function setProfileNotify(ProfileNotify $profile): void
    {
        $this->profileNotify = $profile;
    }

    public function getProfileNotify(): ?ProfileNotify
    {
        return $this->profileNotify;
    }

    public function setAccount(Account $account): void
    {
        $this->account = $account;
        $this->account->setUser($this);
    }

    public function getAccount()
    {
        return $this->account;
    }

    public function setAccountSimple(Account $account): void
    {
        $this->accountSimple = $account;
        $this->accountSimple->setUser($this);
    }

    public function getAccountSimple()
    {
        return $this->accountSimple;
    }

    /**
     * @return Collection<int, Phonenumber>
     */
    public function getPhonenumbers(): Collection
    {
        return $this->phonenumbers;
    }

    public function addPhonenumber(Phonenumber $phonenumber): void
    {
        $this->phonenumbers[] = $phonenumber;
    }

    public function getSortedAscGroups(): Collection
    {
        return $this->sortedAscGroups;
    }

    public function getSortedDescGroups(): Collection
    {
        return $this->sortedDescGroups;
    }

    public function getGroups()
    {
        return $this->groups;
    }

    public function setGroups($groups): void
    {
        $this->groups = $groups;
    }

    public function addGroup(Group $group): void
    {
        $this->groups[] = $group;
    }

    public function removeGroup($name): bool
    {
        foreach ($this->groups as $key => $group) {
            if ($group->getName() === $name) {
                unset($this->groups[$key]);

                return true;
            }
        }

        return false;
    }

    public function addGroupSimple(Group $group): void
    {
        $this->groupsSimple[] = $group;
    }

    public function getUniqueGroups()
    {
        return $this->uniqueGroups;
    }

    public function setUniqueGroups($groups): void
    {
        $this->uniqueGroups = $groups;
    }

    public function addUniqueGroup(Group $group): void
    {
        $this->uniqueGroups[] = $group;
    }

    public function getHits(): int
    {
        return $this->hits;
    }

    public function setHits($hits): void
    {
        $this->hits = $hits;
    }

    public function getCount()
    {
        return $this->count;
    }

    public function setCount($count): void
    {
        $this->count = $count;
    }

    public function getFloatCount()
    {
        return $this->floatCount;
    }

    public function setFloatCount($floatCount): void
    {
        $this->floatCount = $floatCount;
    }

    public function getDecimal128Count()
    {
        return $this->decimal128Count;
    }

    public function setDecimal128Count($decimal128Count): void
    {
        $this->decimal128Count = $decimal128Count;
    }

    public function getSimpleReferenceOneInverse()
    {
        return $this->simpleReferenceOneInverse;
    }

    public function getSimpleReferenceManyInverse()
    {
        return $this->simpleReferenceManyInverse;
    }

    public function incrementCount($num = null): void
    {
        if ($num === null) {
            $this->count++;
        } else {
            $this->count += $num;
        }
    }

    public function incrementFloatCount($num = null): void
    {
        if ($num === null) {
            $this->floatCount++;
        } else {
            $this->floatCount += $num;
        }
    }

    public function incrementDecimal128Count($num = null): void
    {
        $this->decimal128Count = bcadd($this->decimal128Count, $num ?? '1');
    }

    public function setPosts($posts): void
    {
        $this->posts = $posts;
    }

    public function addPost(BlogPost $post): void
    {
        $this->posts[] = $post;
    }

    public function removePost($id): bool
    {
        foreach ($this->posts as $key => $post) {
            if ($post->id === $id) {
                unset($this->posts[$key]);

                return true;
            }
        }

        return false;
    }

    public function getPosts(): Collection
    {
        return $this->posts;
    }

    public function setPhonenumbers($phonenumbers): void
    {
        $this->phonenumbers = $phonenumbers;
    }

    public function addPhonebook(Phonebook $phonebook): void
    {
        $this->phonebooks->add($phonebook);
    }

    public function getPhonebooks(): Collection
    {
        return $this->phonebooks;
    }

    public function removePhonebook(Phonebook $phonebook): void
    {
        $this->phonebooks->removeElement($phonebook);
    }
}
