<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Tests\BaseTestCase;

class GH1964Test extends BaseTestCase
{
    public function testSortMetaShouldReturnCorrectQuery(): void
    {
        $builder = $this->dm->createQueryBuilder(GH1964Document::class);
        $builder->sort(['someField1' => 1, 'someField2' => -1]);
        $builder->sortMeta('score', 'textScore');
        $query   = $builder->getQuery()->getQuery();
        $expects = [
            'someField1' => 1,
            'someField2' => -1,
            'score' => ['$meta' => 'textScore'],
        ];
        self::assertEquals($expects, $query['sort']);
    }
}

#[ODM\Document]
class GH1964Document
{
    /** @var string|null */
    #[ODM\Id]
    private $id;
}
