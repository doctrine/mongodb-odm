<?php

namespace Documents;

/** @Document */
class FriendUser
{
    /** @Id */
    public $id;

    /** @String */
    public $name;

    /**
     * @ReferenceMany(targetDocument="FriendUser", mappedBy="myFriends", cascade={"all"})
     */
    public $friendsWithMe;

    /**
     * @ReferenceMany(targetDocument="FriendUser", inversedBy="friendsWithMe", cascade={"all"})
     */
    public $myFriends;

    public function __construct($name)
    {
        $this->name = $name;
        $this->friendsWithMe = new \Doctrine\Common\Collections\ArrayCollection();
        $this->myFriends = new \Doctrine\Common\Collections\ArrayCollection();
    }

    public function addFriend(FriendUser $user)
    {
        $user->friendsWithMe[] = $this;
        $this->myFriends[] = $user;
    }
}