<?php

declare(strict_types=1);

namespace Doctrine\Tests\ODM\MongoDB\Pagination;

use Doctrine\ODM\MongoDB\Pagination\QueryCursorPaginator;
use Doctrine\ODM\MongoDB\Query\Builder;
use Doctrine\ODM\MongoDB\Tests\BaseTestCase;
use Documents\Tag;

use function array_fill;
use function iterator_to_array;

class QueryCursorPaginatorTest extends BaseTestCase
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
        $paginator = new QueryCursorPaginator($this->createBuilder());

        $results = iterator_to_array($paginator);
        $this->assertCount(24, $results);

        $this->assertSame('0', $results[0]->name);
        $this->assertSame('3', $results[2]->name);
    }

    public function testThirdPage(): void
    {
        $last = $this->createBuilder()->skip(47)->limit(24)->getQuery()->getSingleResult();
        $this->assertInstanceOf(Tag::class, $last);

        $results = iterator_to_array(new QueryCursorPaginator($this->createBuilder(), $last->id));
        $this->assertCount(24, $results);

        $this->assertSame('49', $results[0]->name);
    }

    public function testPartialPage(): void
    {
        $last = $this->createBuilder()->skip(95)->limit(24)->getQuery()->getSingleResult();
        $this->assertInstanceOf(Tag::class, $last);

        $paginator = new QueryCursorPaginator($this->createBuilder(), $last->id);

        $results = iterator_to_array($paginator);
        $this->assertCount(3, $results);

        $this->assertSame('97', $results[0]->name);
    }

    public function testDifferentPerPage(): void
    {
        $paginator = new QueryCursorPaginator($this->createBuilder(), perPage: 12);

        $results = iterator_to_array($paginator);
        $this->assertCount(12, $results);
    }

    public function testDifferentField(): void
    {
        $paginator = new QueryCursorPaginator($this->createBuilder(), after: '5', field: 'name');

        $results = iterator_to_array($paginator);
        $this->assertCount(24, $results);

        $this->assertSame('6', $results[0]->name);
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
