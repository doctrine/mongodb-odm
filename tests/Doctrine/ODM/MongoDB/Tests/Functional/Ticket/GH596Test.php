<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Tests\BaseTest;

class GH596Test extends BaseTest
{
    public function setUp()
    {
        parent::setUp();

        $this->dm->getFilterCollection()->enable('testFilter');
        $filter = $this->dm->getFilterCollection()->getFilter('testFilter');
        $filter->setParameter('class', GH596Document::class);
        $filter->setParameter('field', 'deleted');
        $filter->setParameter('value', false);
    }

    public function testExpressionPreparationDoesNotInjectFilterCriteria()
    {
        $class = GH596Document::class;

        $repository = $this->dm->getRepository($class);
        $qb = $repository->createQueryBuilder();
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

        $this->assertEquals($expected, $query['query']);
    }
}

/** @ODM\Document */
class GH596Document
{
    /** @ODM\Id */
    public $id;

    /** @ODM\Field(type="string") */
    public $name;

    /** @ODM\Field(type="bool") */
    public $deleted = false;
}
