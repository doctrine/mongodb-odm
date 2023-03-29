<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Aggregation\Stage;

use Closure;
use Doctrine\ODM\MongoDB\Aggregation\Expr;
use Doctrine\ODM\MongoDB\Aggregation\Stage\Bucket;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\Tests\Aggregation\AggregationOperatorsProviderTrait;
use Doctrine\ODM\MongoDB\Tests\Aggregation\AggregationTestTrait;
use Doctrine\ODM\MongoDB\Tests\BaseTestCase;
use Documents\CmsComment;
use Documents\User;

class BucketTest extends BaseTestCase
{
    use AggregationOperatorsProviderTrait;
    use AggregationTestTrait;

    /**
     * @param array<string, string>          $expected
     * @param mixed[]|Closure(Expr): mixed[] $args
     *
     * @dataProvider provideGroupAccumulatorExpressionOperators
     */
    public function testGroupAccumulators(array $expected, string $operator, $args): void
    {
        $args = $this->resolveArgs($args);

        $bucketStage = new Bucket($this->getTestAggregationBuilder(), $this->dm, new ClassMetadata(User::class));
        $bucketStage
            ->groupBy('$someField')
            ->boundaries(1, 2, 3)
            ->defaultBucket(0)
            ->output()
            ->field('averageValue')
            ->$operator(...$args);

        self::assertSame([
            '$bucket' => [
                'groupBy' => '$someField',
                'boundaries' => [1, 2, 3],
                'default' => 0,
                'output' => ['averageValue' => $expected],
            ],
        ], $bucketStage->getExpression());
    }

    public function testBuilder(): void
    {
        $builder = $this->getTestAggregationBuilder();
        $builder->bucket()
            ->groupBy('$someField')
            ->boundaries(1, 2, 3)
            ->defaultBucket(0)
            ->output()
            ->field('averageValue')
            ->avg('$value');

        self::assertSame([
            [
                '$bucket' => [
                    'groupBy' => '$someField',
                    'boundaries' => [1, 2, 3],
                    'default' => 0,
                    'output' => ['averageValue' => ['$avg' => '$value']],
                ],
            ],
        ], $builder->getPipeline());
    }

    public function testSkipsUndefinedProperties(): void
    {
        $bucketStage = new Bucket($this->getTestAggregationBuilder(), $this->dm, new ClassMetadata(User::class));
        $bucketStage
            ->groupBy('$someField')
            ->boundaries(1, 2, 3);

        self::assertSame([
            '$bucket' => [
                'groupBy' => '$someField',
                'boundaries' => [1, 2, 3],
            ],
        ], $bucketStage->getExpression());
    }

    public function testFieldNameConversion(): void
    {
        $builder = $this->dm->createAggregationBuilder(CmsComment::class);

        $builder->bucket()
            ->groupBy('$authorIp')
            ->boundaries(1, 2, 3)
            ->defaultBucket(0)
            ->output()
                ->field('averageValue')
                ->avg('$value');

        self::assertEquals(
            [
                [
                    '$bucket' => [
                        'groupBy' => '$ip',
                        'boundaries' => [1, 2, 3],
                        'default' => 0,
                        'output' => ['averageValue' => ['$avg' => '$value']],
                    ],
                ],
            ],
            $builder->getPipeline(),
        );
    }
}
