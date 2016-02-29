<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional;

use Documents\CmsAddress;
use Documents\CmsUser;
use Documents\FriendUser;

class FlushTest extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    /**
     * Given 3 users, userA userB userC
     * userA has a relation to userB
     * userB has a relation to userC
     *
     * With only userA in the uow,
     * If I flush I then have both userA and userB in the uow.
     * If I flush again, I then have all three users in the uow.
     *
     * Each flush fetches and registers the relations of the known objects.
     */
    public function testFlush()
    {
        $userA = new FriendUser('userA');
        $userB = new FriendUser('userB');
        $userC = new FriendUser('userC');

        $userA->addFriend($userB);
        $userB->addFriend($userC);

        // persist all users, flush and clear
        foreach (array($userA, $userB, $userC) as $user) $this->dm->persist($user);
        $this->dm->flush();
        $this->dm->clear();

        $this->assertSize(0);

        $userA = $this->dm->find(get_class($userA), $userA->id);

        // the size is 1. userA is in the uow.
        $this->assertSize(1);

        // first flush
        $this->dm->flush();

        // now the size is 2! userA and userB are in the UOW.
        $this->assertSize(1);

        // second flush
        $this->dm->flush();

        // now the size is 3! userA and userB and userC are in the UOW.
        $this->assertSize(1);
    }

    public function testFlushManyExplicitDocuments()
    {
        $userA = new FriendUser('userA');
        $userB = new FriendUser('userB');
        $userC = new FriendUser('userC');

        $this->dm->persist($userA);
        $this->dm->persist($userB);
        $this->dm->persist($userC);

        $this->dm->flush(array($userA, $userB, $userC));

        $this->assertNotNull($userA->id);
        $this->assertNotNull($userB->id);
        $this->assertNotNull($userC->id);
    }

    public function testFlushSingleManagedDocument()
    {
        $user = new CmsUser;
        $user->name = 'Dominik';
        $user->username = 'domnikl';
        $user->status = 'developer';

        $this->dm->persist($user);
        $this->dm->flush();

        $user->status = 'administrator';
        $this->dm->flush($user);
        $this->dm->clear();

        $user = $this->dm->find(get_class($user), $user->id);
        $this->assertEquals('administrator', $user->status);
    }

    public function testFlushSingleUnmanagedDocument()
    {
        $user = new CmsUser;
        $user->name = 'Dominik';
        $user->username = 'domnikl';
        $user->status = 'developer';

        $this->setExpectedException('InvalidArgumentException', 'Document has to be managed or scheduled for removal for single computation');
        $this->dm->flush($user);
    }

    public function testFlushSingleAndNewDocument()
    {
        $user = new CmsUser;
        $user->name = 'Dominik';
        $user->username = 'domnikl';
        $user->status = 'developer';

        $this->dm->persist($user);
        $this->dm->flush();

        $otherUser = new CmsUser;
        $otherUser->name = 'Dominik2';
        $otherUser->username = 'domnikl2';
        $otherUser->status = 'developer';

        $user->status = 'administrator';

        $this->dm->persist($otherUser);
        $this->dm->flush($user);

        $this->assertTrue($this->dm->contains($otherUser), "Other user is contained in DocumentManager");
        $this->assertTrue($otherUser->id > 0, "other user has an id");
    }

    public function testFlushAndCascadePersist()
    {
        $user = new CmsUser;
        $user->name = 'Dominik';
        $user->username = 'domnikl';
        $user->status = 'developer';

        $this->dm->persist($user);
        $this->dm->flush();

        $address = new CmsAddress();
        $address->city = "Springfield";
        $address->zip = "12354";
        $address->country = "Germany";
        $address->street = "Foo Street";
        $address->user = $user;
        $user->address = $address;

        $this->dm->flush($user);

        $this->assertTrue($this->dm->contains($address), "Other user is contained in DocumentManager");
        $this->assertTrue($address->id > 0, "other user has an id");
    }

    public function testProxyIsIgnored()
    {
        $user = new CmsUser;
        $user->name = 'Dominik';
        $user->username = 'domnikl';
        $user->status = 'developer';

        $this->dm->persist($user);
        $this->dm->flush();
        $this->dm->clear();

        $user = $this->dm->getReference(get_class($user), $user->id);

        $otherUser = new CmsUser;
        $otherUser->name = 'Dominik2';
        $otherUser->username = 'domnikl2';
        $otherUser->status = 'developer';

        $this->dm->persist($otherUser);
        $this->dm->flush($user);

        $this->assertTrue($this->dm->contains($otherUser), "Other user is contained in DocumentManager");
        $this->assertTrue($otherUser->id > 0, "other user has an id");
    }

    protected function assertSize($size)
    {
        $this->assertEquals($size, $this->dm->getUnitOfWork()->size());
    }
}
