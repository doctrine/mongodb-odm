<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Aggregation\Stage;

use Doctrine\ODM\MongoDB\Aggregation\Stage\Bucket;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\Tests\Aggregation\AggregationTestTrait;
use Doctrine\ODM\MongoDB\Tests\BaseTest;
use Documents\CmsComment;
use Documents\User;

class BucketTest extends BaseTest
{
    use AggregationTestTrait;

    public function testBucketStage(): void
    {
        $bucketStage = new Bucket($this->getTestAggregationBuilder(), $this->dm, new ClassMetadata(User::class));
        $bucketStage
            ->groupBy('$someField')
            ->boundaries(1, 2, 3)
            ->defaultBucket(0)
            ->output()
            ->field('averageValue')
            ->avg('$value');

        self::assertSame([
            '$bucket' => [
                'groupBy' => '$someField',
                'boundaries' => [1, 2, 3],
                'default' => 0,
                'output' => ['averageValue' => ['$avg' => '$value']],
            ],
        ], $bucketStage->getExpression());
    }

    public function testBucketFromBuilder(): void
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

    public function testBucketSkipsUndefinedProperties(): void
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
