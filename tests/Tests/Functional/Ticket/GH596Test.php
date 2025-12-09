<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Tests\BaseTestCase;

class GH596Test extends BaseTestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $this->dm->getFilterCollection()->enable('testFilter');
        $filter = $this->dm->getFilterCollection()->getFilter('testFilter');
        $filter->setParameter('class', GH596Document::class);
        $filter->setParameter('field', 'deleted');
        $filter->setParameter('value', false);
    }

    public function testExpressionPreparationDoesNotInjectFilterCriteria(): void
    {
        $class = GH596Document::class;

        $repository = $this->dm->getRepository($class);
        $qb         = $repository->createQueryBuilder();
        $qb->addOr($qb->expr()->field('name')->equals('foo'));
        $qb->addOr($qb->expr()->field('name')->equals('bar'));

        $query = $qb->getQuery();
        $query = $query->getQuery();

        $expected = [
            '$and' => [
                [
                    '$or' => [
                        ['name' => 'foo'],
                        ['name' => 'bar'],
                    ],
                ],
                ['deleted' => false],
            ],
        ];

        self::assertEquals($expected, $query['query']);
    }
}

#[ODM\Document]
class GH596Document
{
    /** @var string|null */
    #[ODM\Id]
    public $id;

    /** @var string|null */
    #[ODM\Field(type: 'string')]
    public $name;

    /** @var bool|null */
    #[ODM\Field(type: 'bool')]
    public $deleted = false;
}
