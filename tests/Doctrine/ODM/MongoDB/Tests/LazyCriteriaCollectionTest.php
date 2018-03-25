<?php

namespace Doctrine\ODM\MongoDB\Tests;

use Doctrine\Common\Collections\Criteria;
use Doctrine\ODM\MongoDB\LazyCriteriaCollection;
use Documents\User;

class LazyCriteriaCollectionTest extends BaseTest
{
    public function testCountIsCached()
    {
        $user = new User();
        $user->setUsername('lhpalacio');
        $this->dm->persist($user);
        $this->dm->flush();

        $queryBuilder = $this->dm->createQueryBuilder(User::class);
        $lazyCriteriaCollection = new LazyCriteriaCollection($queryBuilder, new Criteria());

        self::assertSame(1, $lazyCriteriaCollection->count());
        self::assertSame(1, $lazyCriteriaCollection->count());
        self::assertSame(1, $lazyCriteriaCollection->count());
    }
}
