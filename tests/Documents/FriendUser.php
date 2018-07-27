<?php

declare(strict_types=1);

namespace Documents;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/** @ODM\Document */
class FriendUser
{
    /** @ODM\Id */
    public $id;

    /** @ODM\Field(type="string") */
    public $name;

    /** @ODM\ReferenceMany(targetDocument=FriendUser::class, mappedBy="myFriends", cascade={"all"}) */
    public $friendsWithMe;

    /** @ODM\ReferenceMany(targetDocument=FriendUser::class, inversedBy="friendsWithMe", cascade={"all"}) */
    public $myFriends;

    public function __construct($name)
    {
        $this->name = $name;
        $this->friendsWithMe = new ArrayCollection();
        $this->myFriends = new ArrayCollection();
    }

    public function addFriend(FriendUser $user)
    {
        $user->friendsWithMe[] = $this;
        $this->myFriends[] = $user;
    }
}
