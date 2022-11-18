<?php

declare(strict_types=1);

namespace Documents;

use DateTime;
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use MongoDB\BSON\ObjectId;
use MongoDB\BSON\UTCDateTime;

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
     * @var ObjectId|string|null
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
     * @var UTCDateTime|DateTimeInterface|string
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
     * @var Collection<int, Group>|array<Group>
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
     * @var int
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
     * @var string[]
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

    /** @param ObjectId|string $id */
    public function setId($id): void
    {
        $this->id = $id;
    }

    /** @return string[] */
    public function getLogs(): array
    {
        return $this->logs;
    }

    /** @param string[] $logs */
    public function setLogs(array $logs): void
    {
        $this->logs = $logs;
    }

    public function log(string $log): void
    {
        $this->logs[] = $log;
    }

    /** @return ObjectId|string|null */
    public function getId()
    {
        return $this->id;
    }

    public function setUsername(string $username): void
    {
        $this->username = $username;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setPassword(string $password): void
    {
        $this->password = $password;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    /** @param UTCDateTime|DateTimeInterface|string $createdAt */
    public function setCreatedAt($createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    /** @return UTCDateTime|DateTime|DateTimeInterface|string */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    public function getAddress(): ?Address
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

    public function getProfile(): ?Profile
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

    public function getAccount(): ?Account
    {
        return $this->account;
    }

    public function setAccountSimple(Account $account): void
    {
        $this->accountSimple = $account;
        $this->accountSimple->setUser($this);
    }

    public function getAccountSimple(): ?Account
    {
        return $this->accountSimple;
    }

    /** @return Collection<int, Phonenumber> */
    public function getPhonenumbers(): Collection
    {
        return $this->phonenumbers;
    }

    public function addPhonenumber(Phonenumber $phonenumber): void
    {
        $this->phonenumbers[] = $phonenumber;
    }

    /** @return Collection<int, Group> */
    public function getSortedAscGroups(): Collection
    {
        return $this->sortedAscGroups;
    }

    /** @return Collection<int, Group> */
    public function getSortedDescGroups(): Collection
    {
        return $this->sortedDescGroups;
    }

    /** @return Collection<int, Group>|array<Group> */
    public function getGroups()
    {
        return $this->groups;
    }

    /** @param Collection<int, Group>|array<Group> $groups */
    public function setGroups($groups): void
    {
        $this->groups = $groups;
    }

    public function addGroup(Group $group): void
    {
        $this->groups[] = $group;
    }

    public function removeGroup(string $name): bool
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

    /** @return Collection<int, Group> */
    public function getUniqueGroups(): Collection
    {
        return $this->uniqueGroups;
    }

    /** @param Collection<int, Group> $groups */
    public function setUniqueGroups(Collection $groups): void
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

    public function setHits(int $hits): void
    {
        $this->hits = $hits;
    }

    public function getCount(): ?int
    {
        return $this->count;
    }

    public function setCount(?int $count): void
    {
        $this->count = $count;
    }

    public function getFloatCount(): ?float
    {
        return $this->floatCount;
    }

    public function setFloatCount(?float $floatCount): void
    {
        $this->floatCount = $floatCount;
    }

    public function getDecimal128Count(): ?string
    {
        return $this->decimal128Count;
    }

    public function setDecimal128Count(?string $decimal128Count): void
    {
        $this->decimal128Count = $decimal128Count;
    }

    public function getSimpleReferenceOneInverse(): ?SimpleReferenceUser
    {
        return $this->simpleReferenceOneInverse;
    }

    /** @return Collection<int, SimpleReferenceUser> */
    public function getSimpleReferenceManyInverse(): Collection
    {
        return $this->simpleReferenceManyInverse;
    }

    /** @param float|int|null $num */
    public function incrementCount($num = null): void
    {
        if ($num === null) {
            $this->count++;
        } else {
            $this->count += $num;
        }
    }

    /** @param float|int|null $num */
    public function incrementFloatCount($num = null): void
    {
        if ($num === null) {
            $this->floatCount++;
        } else {
            $this->floatCount += $num;
        }
    }

    public function incrementDecimal128Count(?string $num = null): void
    {
        $this->decimal128Count = bcadd($this->decimal128Count, $num ?? '1');
    }

    /** @param Collection<int, BlogPost> $posts */
    public function setPosts(Collection $posts): void
    {
        $this->posts = $posts;
    }

    public function addPost(BlogPost $post): void
    {
        $this->posts[] = $post;
    }

    public function removePost(string $id): bool
    {
        foreach ($this->posts as $key => $post) {
            if ($post->id === $id) {
                unset($this->posts[$key]);

                return true;
            }
        }

        return false;
    }

    /** @return Collection<int, BlogPost> */
    public function getPosts(): Collection
    {
        return $this->posts;
    }

    /** @param Collection<int, Phonenumber> $phonenumbers */
    public function setPhonenumbers(Collection $phonenumbers): void
    {
        $this->phonenumbers = $phonenumbers;
    }

    public function addPhonebook(Phonebook $phonebook): void
    {
        $this->phonebooks->add($phonebook);
    }

    /** @return Collection<int, Phonebook> */
    public function getPhonebooks(): Collection
    {
        return $this->phonebooks;
    }

    public function removePhonebook(Phonebook $phonebook): void
    {
        $this->phonebooks->removeElement($phonebook);
    }
}
