<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests;

use Documents\User;
use MongoDB\BSON\ObjectId;

class GH385Test extends BaseTest
{
    public function testQueryBuilderShouldPrepareUnmappedFields()
    {
        $identifier = new ObjectId();

        $qb = $this->dm->createQueryBuilder(User::class)
            ->upsert()
            ->updateOne()
            ->field('id')->equals($identifier)
            ->field('foo.bar.level3a')->inc(1)
            ->field('foo.bar.level3b')->inc(1);

        $debug = $qb->getQuery()->getQuery();

        $this->assertEquals(['$inc' => ['foo.bar.level3a' => 1, 'foo.bar.level3b' => 1]], $debug['newObj']);

        $qb->getQuery()->execute();

        $check = $this->dm->getDocumentCollection(User::class)->findOne(['_id' => $identifier]);
        $this->assertNotNull($check);
        $this->assertTrue(isset($check['foo']['bar']['level3a']));
        $this->assertTrue(isset($check['foo']['bar']['level3b']));
        $this->assertEquals(1, $check['foo']['bar']['level3a']);
        $this->assertEquals(1, $check['foo']['bar']['level3b']);
    }
}
