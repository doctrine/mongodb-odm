<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional;

use Doctrine\ODM\MongoDB\EagerCursor;
use Doctrine\ODM\MongoDB\Tests\BaseTest;
use Documents\User;

class EagerCursorTest extends BaseTest
{
    public function testCountFoundOnlyBehaviorWithPrimingEnabled()
    {
        $usernames = array('David', 'Xander', 'Alex', 'Kris', 'Jon');

        foreach ($usernames as $username){
            $user = new User();
            $user->setUsername($username);
            $this->dm->persist($user);
        }

        $this->dm->flush();

        /* @var EagerCursor $cursor */
        $cursor = $this->dm->createQueryBuilder('Documents\User')
            ->sort('username', 'asc')
            ->field('groups')->prime(true)
            ->limit(2)
            ->getQuery()
            ->execute();

        $this->assertInstanceOf('\Doctrine\ODM\MongoDB\EagerCursor', $cursor);

        $this->assertEquals(5, $cursor->count());
        $this->assertEquals(2, $cursor->count(true));
    }
}
