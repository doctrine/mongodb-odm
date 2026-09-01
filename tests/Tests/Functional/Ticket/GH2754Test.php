<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\Mapping\Attribute as ODM;
use Doctrine\ODM\MongoDB\Tests\BaseTestCase;

class GH2754Test extends BaseTestCase
{
    public function testSiblingCollectionsWithSharedPrefixArePersisted(): void
    {
        $user          = new GH2754User();
        $user->profile = new GH2754Profile();
        $user->profile->friends->add(new GH2754Friend('alice'));
        $user->profile->friendsPending->add(new GH2754FriendRequest('bob'));
        $this->dm->persist($user);
        $this->dm->flush();
        $this->dm->clear();

        $user = $this->dm->find(GH2754User::class, $user->id);
        self::assertNotNull($user);

        // Replace both sibling collections within the same flush. The field name "friends"
        // is a prefix of "friendsPending", which previously caused the latter to be dropped.
        $user->profile->friends        = new ArrayCollection([new GH2754Friend('carol')]);
        $user->profile->friendsPending = new ArrayCollection([new GH2754FriendRequest('dave'), new GH2754FriendRequest('erin')]);
        $this->dm->flush();
        $this->dm->clear();

        $user = $this->dm->find(GH2754User::class, $user->id);
        self::assertNotNull($user);
        self::assertCount(1, $user->profile->friends);
        self::assertCount(2, $user->profile->friendsPending, 'Sibling collection with prefixed name must be persisted');
        self::assertSame('dave', $user->profile->friendsPending[0]->name);
        self::assertSame('erin', $user->profile->friendsPending[1]->name);
    }
}

#[ODM\Document]
class GH2754User
{
    /** @var string|null */
    #[ODM\Id]
    public $id;

    #[ODM\EmbedOne(targetDocument: GH2754Profile::class)]
    public ?GH2754Profile $profile = null;
}

#[ODM\EmbeddedDocument]
class GH2754Profile
{
    /** @var Collection<int, GH2754Friend> */
    #[ODM\EmbedMany(targetDocument: GH2754Friend::class)]
    public Collection $friends;

    /** @var Collection<int, GH2754FriendRequest> */
    #[ODM\EmbedMany(targetDocument: GH2754FriendRequest::class)]
    public Collection $friendsPending;

    public function __construct()
    {
        $this->friends        = new ArrayCollection();
        $this->friendsPending = new ArrayCollection();
    }
}

#[ODM\EmbeddedDocument]
class GH2754Friend
{
    #[ODM\Field(type: 'string')]
    public string $name;

    public function __construct(string $name)
    {
        $this->name = $name;
    }
}

#[ODM\EmbeddedDocument]
class GH2754FriendRequest
{
    #[ODM\Field(type: 'string')]
    public string $name;

    public function __construct(string $name)
    {
        $this->name = $name;
    }
}
