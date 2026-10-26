<?php

declare(strict_types=1);

namespace Doctrine\Tests\ODM\MongoDB\Pagination;

use Doctrine\ODM\MongoDB\Pagination\QueryOffsetPaginator;
use Doctrine\ODM\MongoDB\Query\Builder;
use Doctrine\ODM\MongoDB\Tests\BaseTestCase;
use Documents\Tag;

use function array_fill;
use function count;
use function iterator_to_array;

class QueryOffsetPaginatorTest extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->prepareData();
    }

    protected function tearDown(): void
    {
        $this->dm->getDocumentCollection(Tag::class)->drop();

        parent::tearDown();
    }

    public function testFirstPage(): void
    {
        $paginator = new QueryOffsetPaginator($this->createBuilder(), 1);

        $this->assertSame(5, count($paginator));

        $results = iterator_to_array($paginator);
        $this->assertCount(24, $results);

        $this->assertSame('0', $results[0]->name);
        $this->assertSame('3', $results[2]->name);
    }

    public function testThirdPage(): void
    {
        $this->assertSame(5, count(new QueryOffsetPaginator($this->createBuilder(), 3)));

        $results = iterator_to_array(new QueryOffsetPaginator($this->createBuilder(), 3));
        $this->assertCount(24, $results);

        $this->assertSame('49', $results[0]->name);
    }

    public function testPartialPage(): void
    {
        $paginator = new QueryOffsetPaginator($this->createBuilder(), 5);

        $this->assertSame(5, count($paginator));

        $results = iterator_to_array($paginator);
        $this->assertCount(3, $results);

        $this->assertSame('97', $results[0]->name);
    }

    public function testDifferentPerPage(): void
    {
        $paginator = new QueryOffsetPaginator($this->createBuilder(), 1, 12);

        $this->assertSame(9, count($paginator));

        $results = iterator_to_array($paginator);
        $this->assertCount(12, $results);
    }

    private function prepareData(): void
    {
        foreach (array_fill(0, 100, true) as $key => $t) {
            $this->dm->persist(new Tag((string) $key));
        }

        $this->dm->flush();
    }

    private function createBuilder(): Builder
    {
        $builder = $this->dm->createQueryBuilder(Tag::class);
        $builder
            ->find()
                ->field('name')->notEqual('2')
                ->sort('id', 1);

        return $builder;
    }
}
