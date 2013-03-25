<?php

namespace Doctrine\ODM\MongoDB\Tests;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

class GH385Test extends BaseTest
{
    public function testQueryBuilderShouldPrepareUnmappedFields()
    {
        $mongoId = new \MongoId();

        $qb = $this->dm->createQueryBuilder('Documents\User')
            ->upsert()
            ->update()
            ->field('id')->equals($mongoId)
            ->field('foo.bar.level3a')->inc(1)
            ->field('foo.bar.level3b')->inc(1);

        $debug = $qb->getQuery()->getQuery();

        $this->assertEquals(array('$inc' => array('foo.bar.level3a' => 1, 'foo.bar.level3b' => 1)), $debug['newObj']);

        $qb->getQuery()->execute();

        $check = $this->dm->getDocumentCollection('Documents\User')->findOne(array('_id' => $mongoId));
        $this->assertNotNull($check);
        $this->assertTrue(isset($check['foo']['bar']['level3a']));
        $this->assertTrue(isset($check['foo']['bar']['level3b']));
        $this->assertEquals(1, $check['foo']['bar']['level3a']);
        $this->assertEquals(1, $check['foo']['bar']['level3b']);
    }
}
