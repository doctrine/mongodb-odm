<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Aggregation\Stage;

use Doctrine\ODM\MongoDB\Aggregation\Stage\BucketAuto;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\Tests\Aggregation\AggregationTestTrait;
use Doctrine\ODM\MongoDB\Tests\BaseTest;
use Documents\CmsComment;
use Documents\User;

class BucketAutoTest extends BaseTest
{
    use AggregationTestTrait;

    public function testBucketAutoStage(): void
    {
        $bucketStage = new BucketAuto($this->getTestAggregationBuilder(), $this->dm, new ClassMetadata(User::class));
        $bucketStage
            ->groupBy('$someField')
            ->buckets(3)
            ->granularity('R10')
            ->output()
            ->field('averageValue')
            ->avg('$value');

        self::assertSame([
            '$bucketAuto' => [
                'groupBy' => '$someField',
                'buckets' => 3,
                'granularity' => 'R10',
                'output' => ['averageValue' => ['$avg' => '$value']],
            ],
        ], $bucketStage->getExpression());
    }

    public function testBucketAutoFromBuilder(): void
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

    public function testBucketAutoSkipsUndefinedProperties(): void
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
