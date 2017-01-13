<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

class GH1294Test extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    public function testRegexSearchOnIdentifierWithUuidStrategy()
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
            ->equals(new \MongoDB\BSON\Regex("^bbb.*$", 'i'))
            ->getQueryArray();

        $this->assertInstanceOf(\MongoDB\BSON\Regex::class, $res['_id']);
        $this->assertEquals('^bbb.*$', $res['_id']->getPattern());
        $this->assertEquals('i', $res['_id']->getFlags());
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
