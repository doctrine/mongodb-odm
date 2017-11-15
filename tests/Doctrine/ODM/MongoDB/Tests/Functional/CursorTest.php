<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional;

use Doctrine\ODM\MongoDB\Cursor;
use Documents\User;
use MongoDB\Driver\Cursor as BaseCursor;

class CursorTest extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    public function testCursorShouldHydrateResults()
    {
        $user = new User();
        $user->setUsername('foo');

        $this->dm->persist($user);
        $this->dm->flush();

        $cursor = $this->uow->getDocumentPersister('Documents\User')->loadAll();

        $this->assertSame($user, $cursor->current());
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
        $this->assertInstanceOf(Cursor::class, $cursor);
        $this->assertInstanceOf(BaseCursor::class, $cursor->getBaseCursor());
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
