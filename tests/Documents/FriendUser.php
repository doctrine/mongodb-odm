<?php

declare(strict_types=1);

namespace Documents;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/** @ODM\Document */
class FriendUser
{
    /**
     * @ODM\Id
     *
     * @var string|null
     */
    public $id;

    /**
     * @ODM\Field(type="string")
     *
     * @var string
     */
    public $name;

    /**
     * @ODM\ReferenceMany(targetDocument=FriendUser::class, mappedBy="myFriends", cascade={"all"})
     *
     * @var Collection<int, FriendUser>
     */
    public $friendsWithMe;

    /**
     * @ODM\ReferenceMany(targetDocument=FriendUser::class, inversedBy="friendsWithMe", cascade={"all"})
     *
     * @var Collection<int, FriendUser>
     */
    public $myFriends;

    public function __construct(string $name)
    {
        $this->name          = $name;
        $this->friendsWithMe = new ArrayCollection();
        $this->myFriends     = new ArrayCollection();
    }

    public function addFriend(FriendUser $user): void
    {
        $user->friendsWithMe[] = $this;
        $this->myFriends[]     = $user;
    }
}
