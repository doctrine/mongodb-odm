<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Aggregation\Stage;

use Closure;
use Doctrine\ODM\MongoDB\Aggregation\Expr;
use Doctrine\ODM\MongoDB\Aggregation\Stage\BucketAuto;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\Tests\Aggregation\AggregationOperatorsProviderTrait;
use Doctrine\ODM\MongoDB\Tests\Aggregation\AggregationTestTrait;
use Doctrine\ODM\MongoDB\Tests\BaseTest;
use Documents\CmsComment;
use Documents\User;

class BucketAutoTest extends BaseTest
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

        $bucketStage = new BucketAuto($this->getTestAggregationBuilder(), $this->dm, new ClassMetadata(User::class));
        $bucketStage
            ->groupBy('$someField')
            ->buckets(3)
            ->granularity('R10')
            ->output()
            ->field('averageValue')
            ->$operator(...$args);

        self::assertSame([
            '$bucketAuto' => [
                'groupBy' => '$someField',
                'buckets' => 3,
                'granularity' => 'R10',
                'output' => ['averageValue' => $expected],
            ],
        ], $bucketStage->getExpression());
    }

    public function testFromBuilder(): void
    {
        $builder = $this->getTestAggregationBuilder();
        $builder->bucketAuto()
            ->groupBy('$someField')
            ->buckets(3)
            ->granularity('R10')
            ->output()
            ->field('averageValue')
            ->avg('$value');

        self::assertSame([
            [
                '$bucketAuto' => [
                    'groupBy' => '$someField',
                    'buckets' => 3,
                    'granularity' => 'R10',
                    'output' => ['averageValue' => ['$avg' => '$value']],
                ],
            ],
        ], $builder->getPipeline());
    }

    public function testSkipsUndefinedProperties(): void
    {
        $bucketStage = new BucketAuto($this->getTestAggregationBuilder(), $this->dm, new ClassMetadata(User::class));
        $bucketStage
            ->groupBy('$someField')
            ->buckets(3);

        self::assertSame([
            '$bucketAuto' => [
                'groupBy' => '$someField',
                'buckets' => 3,
            ],
        ], $bucketStage->getExpression());
    }

    public function testFieldNameConversion(): void
    {
        $builder = $this->dm->createAggregationBuilder(CmsComment::class);

        $builder->bucketAuto()
            ->groupBy('$authorIp')
            ->buckets(3)
            ->granularity('R10')
            ->output()
                ->field('averageValue')
                ->avg('$value');

        self::assertEquals(
            [
                [
                    '$bucketAuto' => [
                        'groupBy' => '$ip',
                        'buckets' => 3,
                        'granularity' => 'R10',
                        'output' => ['averageValue' => ['$avg' => '$value']],
                    ],
                ],
            ],
            $builder->getPipeline(),
        );
    }
}
