<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Tests\BaseTestCase;
use MongoDB\BSON\Regex;

class GH1294Test extends BaseTestCase
{
    public function testRegexSearchOnIdentifierWithUuidStrategy(): void
    {
        $user1       = new GH1294User();
        $user1->id   = 'aaa111aaa';
        $user1->name = 'Steven';

        $user2       = new GH1294User();
        $user2->id   = 'bbb111bbb';
        $user2->name = 'Jeff';

        $this->dm->persist($user1);
        $this->dm->persist($user2);
        $this->dm->flush();
        $this->dm->clear();

        $qb = $this->dm->createQueryBuilder(GH1294User::class);

        $res = $qb->field('id')
            ->equals(new Regex('^bbb.*$', 'i'))
            ->getQueryArray();

        self::assertInstanceOf(Regex::class, $res['_id']);
        self::assertEquals('^bbb.*$', $res['_id']->getPattern());
        self::assertEquals('i', $res['_id']->getFlags());
    }
}

#[ODM\Document]
class GH1294User
{
    /** @var string|null */
    #[ODM\Id(strategy: 'UUID', type: 'string')]
    public $id;

    /** @var string|null */
    #[ODM\Field(type: 'string')]
    public $name = '';

    public function getId(): ?string
    {
        return $this->id;
    }
}
