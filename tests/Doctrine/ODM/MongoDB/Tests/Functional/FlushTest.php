<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional;

use Doctrine\ODM\MongoDB\Tests\BaseTest;
use Documents\FriendUser;
use function get_class;

class FlushTest extends BaseTest
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
        foreach ([$userA, $userB, $userC] as $user) {
            $this->dm->persist($user);
        }
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

    protected function assertSize($size)
    {
        $this->assertEquals($size, $this->dm->getUnitOfWork()->size());
    }
}
