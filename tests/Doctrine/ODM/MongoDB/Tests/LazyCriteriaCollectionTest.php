<?php

namespace Doctrine\ODM\MongoDB\Tests;

use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ODM\MongoDB\LazyCriteriaCollection;
use Documents\User;

class LazyCriteriaCollectionTest extends BaseTest
{
    public function testCountIsCached()
    {
        for ($i=0; $i < 10; $i++) {
            $user = new User();
            $user->setUsername('lhpalacio');

            $this->dm->persist($user);
        }

        $this->dm->flush();

        $queryBuilder = $this->dm->createQueryBuilder(User::class);

        $criteria = new Criteria();
        $criteria->setFirstResult(5)->setMaxResults(3);

        $lazyCriteriaCollection = new LazyCriteriaCollection($queryBuilder, $criteria);

        self::assertSame(3, $lazyCriteriaCollection->count());
        self::assertSame(3, $lazyCriteriaCollection->count());
        self::assertSame(3, $lazyCriteriaCollection->count());
    }

    public function testCountIsCachedEvenWithZeroResult()
    {
        $queryBuilder = $this->dm->createQueryBuilder(User::class);

        $lazyCriteriaCollection = new LazyCriteriaCollection($queryBuilder, new Criteria());

        self::assertSame(0, $lazyCriteriaCollection->count());
        self::assertSame(0, $lazyCriteriaCollection->count());
        self::assertSame(0, $lazyCriteriaCollection->count());
    }

    public function testIsEmptyIsFalseIfCountIsNotZero()
    {
        $user = new User();
        $user->setUsername('lhpalacio');
        $this->dm->persist($user);
        $this->dm->flush();

        $queryBuilder = $this->dm->createQueryBuilder(User::class);

        $lazyCriteriaCollection = new LazyCriteriaCollection($queryBuilder, new Criteria());

        self::assertFalse($lazyCriteriaCollection->isEmpty());
    }

    public function testMatching()
    {
        $foo = new User();
        $foo->setUsername('foo');
        $this->dm->persist($foo);

        $bar = new User();
        $bar->setUsername('bar');
        $this->dm->persist($bar);

        $baz = new User();
        $baz->setUsername('baz');
        $this->dm->persist($baz);

        $this->dm->flush();

        $queryBuilder = $this->dm->createQueryBuilder(User::class);
        $criteria = new Criteria();
        $criteria->where($criteria->expr()->eq('username', 'foo'));

        $lazyCriteriaCollection = new LazyCriteriaCollection($queryBuilder, $criteria);
        $filtered = $lazyCriteriaCollection->matching($criteria);

        self::assertInstanceOf(Collection::class, $filtered);
        self::assertEquals([$foo], $filtered->toArray());
        self::assertEquals([$foo], $lazyCriteriaCollection->matching($criteria)->toArray());
    }
}
