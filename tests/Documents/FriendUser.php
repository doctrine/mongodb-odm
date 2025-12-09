<?php

declare(strict_types=1);

namespace Documents;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

#[ODM\Document]
class FriendUser
{
    /** @var string|null */
    #[ODM\Id]
    public $id;

    /** @var string */
    #[ODM\Field(type: 'string')]
    public $name;

    /** @var Collection<int, FriendUser> */
    #[ODM\ReferenceMany(targetDocument: self::class, mappedBy: 'myFriends', cascade: ['all'])]
    public $friendsWithMe;

    /** @var Collection<int, FriendUser> */
    #[ODM\ReferenceMany(targetDocument: self::class, inversedBy: 'friendsWithMe', cascade: ['all'])]
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
