<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Aggregation\Stage;

use Doctrine\ODM\MongoDB\Aggregation\Stage\VectorSearch;
use Doctrine\ODM\MongoDB\Tests\Aggregation\AggregationTestTrait;
use Doctrine\ODM\MongoDB\Tests\BaseTestCase;
use MongoDB\BSON\Binary;

class VectorSearchTest extends BaseTestCase
{
    use AggregationTestTrait;

    public function testEmptyStage(): void
    {
        $stage = new VectorSearch($this->getTestAggregationBuilder());
        self::assertSame(['$vectorSearch' => []], $stage->getExpression());
    }

    public function testExact(): void
    {
        $stage = new VectorSearch($this->getTestAggregationBuilder());
        $stage->exact(true);
        self::assertSame(['$vectorSearch' => ['exact' => true]], $stage->getExpression());
    }

    public function testFilter(): void
    {
        $builder = $this->getTestAggregationBuilder();
        $stage   = new VectorSearch($builder);
        $stage->filter($builder->matchExpr()->field('status')->notEqual('inactive'));
        self::assertSame(['$vectorSearch' => ['filter' => ['status' => ['$ne' => 'inactive']]]], $stage->getExpression());
    }

    public function testIndex(): void
    {
        $stage = new VectorSearch($this->getTestAggregationBuilder());
        $stage->index('myIndex');
        self::assertSame(['$vectorSearch' => ['index' => 'myIndex']], $stage->getExpression());
    }

    public function testLimit(): void
    {
        $stage = new VectorSearch($this->getTestAggregationBuilder());
        $stage->limit(10);
        self::assertSame(['$vectorSearch' => ['limit' => 10]], $stage->getExpression());
    }

    public function testNumCandidates(): void
    {
        $stage = new VectorSearch($this->getTestAggregationBuilder());
        $stage->numCandidates(5);
        self::assertSame(['$vectorSearch' => ['numCandidates' => 5]], $stage->getExpression());
    }

    public function testPath(): void
    {
        $stage = new VectorSearch($this->getTestAggregationBuilder());
        $stage->path('vectorField');
        self::assertSame(['$vectorSearch' => ['path' => 'vectorField']], $stage->getExpression());
    }

    public function testQueryVector(): void
    {
        $stage = new VectorSearch($this->getTestAggregationBuilder());
        $stage->queryVector([1, 2, 3]);
        self::assertSame(['$vectorSearch' => ['queryVector' => [1, 2, 3]]], $stage->getExpression());
    }

    public function testQueryVectorAcceptsBinary(): void
    {
        $stage        = new VectorSearch($this->getTestAggregationBuilder());
        $binaryVector = new Binary("\x01\x02\x03", 9);
        $stage->queryVector($binaryVector);
        self::assertSame(['$vectorSearch' => ['queryVector' => $binaryVector]], $stage->getExpression());
    }

    public function testChainingAllOptions(): void
    {
        $builder = $this->getTestAggregationBuilder();
        $stage   = (new VectorSearch($builder))
            ->exact(false)
            ->filter($builder->matchExpr()->field('status')->notEqual('inactive'))
            ->index('idx')
            ->limit(7)
            ->numCandidates(3)
            ->path('vec')
            ->queryVector([0.1, 0.2]);
        self::assertSame([
            '$vectorSearch' => [
                'exact' => false,
                'filter' => ['status' => ['$ne' => 'inactive']],
                'index' => 'idx',
                'limit' => 7,
                'numCandidates' => 3,
                'path' => 'vec',
                'queryVector' => [0.1, 0.2],
            ],
        ], $stage->getExpression());
    }
}
