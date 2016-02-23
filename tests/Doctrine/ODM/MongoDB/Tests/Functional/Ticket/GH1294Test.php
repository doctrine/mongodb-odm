<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

class GH1294Test extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    public function testMongoRegexSearchOnIdentifierWithUuidStrategy()
    {
        $userClass = __NAMESPACE__ . '\GH1294User';

        $user1 = new GH1294User();
        $user1->id = 'aaa111aaa';
        $user1->name = 'Steven';

        $user2 = new GH1294User();
        $user2->id = 'bbb111bbb';
        $user2->name = 'Jeff';

        $this->dm->persist($user1);
        $this->dm->persist($user2);
        $this->dm->flush();
        $this->dm->clear();

        $qb = $this->dm->createQueryBuilder($userClass);

        $res = $qb->field('id')
            ->equals(new \MongoRegex("/^bbb.*$/i"))
            ->getQueryArray();

        $this->assertTrue(($res['_id'] instanceof \MongoRegex));
        $this->assertEquals('^bbb.*$', $res['_id']->regex);
        $this->assertEquals('i', $res['_id']->flags);
    }
}

/** @ODM\Document */
class GH1294User
{
    /** @ODM\Id(strategy="UUID", type="string") */
    public $id;

    /** @ODM\Field(type="string") */
    public $name = false;

    // Return the identifier without triggering Proxy initialization
    public function getId()
    {
        return $this->id;
    }
}
