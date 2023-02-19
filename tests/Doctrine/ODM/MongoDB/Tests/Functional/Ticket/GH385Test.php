<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\ODM\MongoDB\Tests\BaseTestCase;
use Documents\User;
use MongoDB\BSON\ObjectId;

class GH385Test extends BaseTestCase
{
    public function testQueryBuilderShouldPrepareUnmappedFields(): void
    {
        $identifier = new ObjectId();

        $qb = $this->dm->createQueryBuilder(User::class)
            ->upsert()
            ->updateOne()
            ->field('id')->equals($identifier)
            ->field('foo.bar.level3a')->inc(1)
            ->field('foo.bar.level3b')->inc(1);

        $debug = $qb->getQuery()->getQuery();

        self::assertEquals(['$inc' => ['foo.bar.level3a' => 1, 'foo.bar.level3b' => 1]], $debug['newObj']);

        $qb->getQuery()->execute();

        $check = $this->dm->getDocumentCollection(User::class)->findOne(['_id' => $identifier]);
        self::assertNotNull($check);
        self::assertTrue(isset($check['foo']['bar']['level3a']));
        self::assertTrue(isset($check['foo']['bar']['level3b']));
        self::assertEquals(1, $check['foo']['bar']['level3a']);
        self::assertEquals(1, $check['foo']['bar']['level3b']);
    }
}
