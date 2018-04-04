<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Tests\BaseTest;
use MongoDB\BSON\Regex;

class GH1294Test extends BaseTest
{
    public function testRegexSearchOnIdentifierWithUuidStrategy()
    {
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

        $qb = $this->dm->createQueryBuilder(GH1294User::class);

        $res = $qb->field('id')
            ->equals(new Regex('^bbb.*$', 'i'))
            ->getQueryArray();

        $this->assertInstanceOf(Regex::class, $res['_id']);
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

    public function getId()
    {
        return $this->id;
    }
}
