<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Aggregation\Stage;

use Doctrine\ODM\MongoDB\Aggregation\Builder;
use Doctrine\ODM\MongoDB\Aggregation\Stage\VectorSearch;
use Doctrine\ODM\MongoDB\Tests\Aggregation\AggregationTestTrait;
use Doctrine\ODM\MongoDB\Tests\BaseTestCase;
use Documents\User;
use Documents\VectorEmbedding;
use MongoDB\BSON\Binary;
use MongoDB\BSON\VectorType;

use function enum_exists;

class VectorSearchTest extends BaseTestCase
{
    use AggregationTestTrait;

    public function testEmptyStage(): void
    {
        [$stage] = $this->createVectorSearchStage();
        self::assertSame(['$vectorSearch' => []], $stage->getExpression());
    }

    public function testExact(): void
    {
        [$stage, $builder] = $this->createVectorSearchStage();
        $stage->exact(true);
        self::assertSame(['$vectorSearch' => ['exact' => true]], $stage->getExpression());
    }

    public function testFilter(): void
    {
        [$stage, $builder] = $this->createVectorSearchStage();
        $stage->filter($builder->matchExpr()->field('status')->notEqual('inactive'));
        self::assertSame(['$vectorSearch' => ['filter' => ['status' => ['$ne' => 'inactive']]]], $stage->getExpression());
    }

    public function testIndex(): void
    {
        [$stage] = $this->createVectorSearchStage();
        $stage->index('myIndex');
        self::assertSame(['$vectorSearch' => ['index' => 'myIndex']], $stage->getExpression());
    }

    public function testLimit(): void
    {
        [$stage] = $this->createVectorSearchStage();
        $stage->limit(10);
        self::assertSame(['$vectorSearch' => ['limit' => 10]], $stage->getExpression());
    }

    public function testNumCandidates(): void
    {
        [$stage] = $this->createVectorSearchStage();
        $stage->numCandidates(5);
        self::assertSame(['$vectorSearch' => ['numCandidates' => 5]], $stage->getExpression());
    }

    public function testPath(): void
    {
        [$stage] = $this->createVectorSearchStage();
        $stage->path('vectorField');
        self::assertSame(['$vectorSearch' => ['path' => 'vectorField']], $stage->getExpression());
    }

    public function testPathIsPrepared(): void
    {
        [$stage] = $this->createVectorSearchStage(VectorEmbedding::class);
        $stage->path('vectorFloat');
        self::assertSame(['$vectorSearch' => ['path' => 'db_vector_float']], $stage->getExpression());
    }

    public function testQueryVector(): void
    {
        [$stage] = $this->createVectorSearchStage();
        $stage->queryVector([1, 2, 3]);
        self::assertSame(['$vectorSearch' => ['queryVector' => [1, 2, 3]]], $stage->getExpression());
    }

    public function testQueryVectorAcceptsBinary(): void
    {
        [$stage] = $this->createVectorSearchStage();
        // @phpstan-ignore class.notFound (requires ext-mongodb 2.2+)
        if (enum_exists(VectorType::class)) {
            // @phpstan-ignore staticMethod.notFound (requires ext-mongodb 2.2+)
            $binaryVector = Binary::fromVector([1, 2, 3], VectorType::Int8);
            self::assertInstanceOf(Binary::class, $binaryVector);
        } else {
            $binaryVector = new Binary("\x03\x00\x01\x02\x03", 9);
        }

        $stage->queryVector($binaryVector);
        self::assertSame(['$vectorSearch' => ['queryVector' => $binaryVector]], $stage->getExpression());
    }

    public function testChainingAllOptions(): void
    {
        [$stage, $builder] = $this->createVectorSearchStage();
        $stage
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

    /**
     * @param class-string $className
     *
     * @return array{0: VectorSearch, 1: Builder}
     */
    private function createVectorSearchStage(string $className = User::class): array
    {
        return [
            new VectorSearch($builder = $this->getTestAggregationBuilder($className), $this->dm->getUnitOfWork()->getDocumentPersister($className)),
            $builder,
        ];
    }
}
