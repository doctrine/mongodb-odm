<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional;

use Doctrine\ODM\MongoDB\Cursor;
use Documents\User;

class CursorTest extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    public function testCursorShouldHydrateResults()
    {
        $user = new User();
        $user->setUsername('foo');

        $this->dm->persist($user);
        $this->dm->flush();

        $cursor = $this->uow->getDocumentPersister('Documents\User')->loadAll();

        $cursor->next();
        $this->assertSame($user, $cursor->current());

        $cursor->reset();
        $this->assertSame($user, $cursor->getNext());

        $cursor->reset();
        $this->assertSame($user, $cursor->getSingleResult());

        $cursor->reset();
        $this->assertSame(array($user), $cursor->toArray(false));
    }

    public function testRecreateShouldPreserveSorting()
    {
        $usernames = array('David', 'Xander', 'Alex', 'Kris', 'Jon');

        foreach ($usernames as $username){
            $user = new User();
            $user->setUsername($username);
            $this->dm->persist($user);
        }

        $this->dm->flush();

        $qb = $this->dm->createQueryBuilder('Documents\User')
            ->sort('username', 'asc');

        $cursor = $qb->getQuery()->execute();
        sort($usernames);

        foreach ($usernames as $username) {
            $this->assertEquals($username, $cursor->getNext()->getUsername());
        }

        $cursor->recreate();

        foreach ($usernames as $username) {
            $this->assertEquals($username, $cursor->getNext()->getUsername());
        }
    }

    public function testGetSingleResultPreservesLimit()
    {
        $usernames = array('David', 'Xander', 'Alex', 'Kris', 'Jon');

        foreach ($usernames as $username){
            $user = new User();
            $user->setUsername($username);
            $this->dm->persist($user);
        }

        $this->dm->flush();

        $cursor = $this->dm->createQueryBuilder('Documents\User')
            ->sort('username', 'asc')
            ->limit(2)
            ->getQuery()
            ->execute();

        $user = $cursor->getSingleResult();

        $users = $cursor->toArray();
        $this->assertCount(2, $users);
    }

    public function runningEagerQueryWrapsEagerCursor()
    {
        $qb = $this->dm->createQueryBuilder('Documents\User')
            ->eagerCursor(true);

        $cursor = $qb->getQuery()->execute();
        $this->assertInstanceOf('Doctrine\ODM\MongoDB\EagerCursor', $cursor);
        $this->assertInstanceOf('Doctrine\MongoDB\EagerCursor', $cursor->getBaseCursor());
    }

    public function testCountFoundOnlyBehavior()
    {
        $usernames = array('David', 'Xander', 'Alex', 'Kris', 'Jon');

        foreach ($usernames as $username){
            $user = new User();
            $user->setUsername($username);
            $this->dm->persist($user);
        }

        $this->dm->flush();

        $cursor = $this->dm->createQueryBuilder('Documents\User')
            ->sort('username', 'asc')
            ->limit(2)
            ->getQuery()
            ->execute();

        $this->assertEquals(5, $cursor->count());
        $this->assertEquals(2, $cursor->count(true));
    }

    public function testPrimeEmptySingleResult()
    {
        /* @var Cursor $cursor */
        $cursor = $this->dm->createQueryBuilder('Documents\User')
            ->field('groups')->prime(true)
            ->getQuery()
            ->execute();

        $result = $cursor->getSingleResult();

        $this->assertNull($result);
    }
}
