<?php

namespace Doctrine\ODM\MongoDB\Tests\Aggregation\Stage;

use Doctrine\ODM\MongoDB\Aggregation\Stage\Bucket;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\Tests\Aggregation\AggregationTestTrait;
use Documents\CmsComment;
use Documents\User;

class BucketAutoTest extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    use AggregationTestTrait;

    public function testBucketStage()
    {
        $bucketStage = new Bucket($this->getTestAggregationBuilder(), $this->dm, new ClassMetadata(User::class));
        $bucketStage
            ->groupBy('$someField')
            ->boundaries(1, 2, 3)
            ->defaultBucket(0)
            ->output()
            ->field('averageValue')
            ->avg('$value');

        $this->assertSame(['$bucket' => [
            'groupBy' => '$someField',
            'boundaries' => [1, 2, 3],
            'default' => 0,
            'output' => ['averageValue' => ['$avg' => '$value']]
        ]], $bucketStage->getExpression());
    }

    public function testBucketFromBuilder()
    {
        $builder = $this->getTestAggregationBuilder();
        $builder->bucket()
            ->groupBy('$someField')
            ->boundaries(1, 2, 3)
            ->defaultBucket(0)
            ->output()
            ->field('averageValue')
            ->avg('$value');

        $this->assertSame([['$bucket' => [
            'groupBy' => '$someField',
            'boundaries' => [1, 2, 3],
            'default' => 0,
            'output' => ['averageValue' => ['$avg' => '$value']]
        ]]], $builder->getPipeline());
    }

    public function testBucketSkipsUndefinedProperties()
    {
        $bucketStage = new Bucket($this->getTestAggregationBuilder(), $this->dm, new ClassMetadata(User::class));
        $bucketStage
            ->groupBy('$someField')
            ->boundaries(1, 2, 3);

        $this->assertSame(['$bucket' => [
            'groupBy' => '$someField',
            'boundaries' => [1, 2, 3],
        ]], $bucketStage->getExpression());
    }

    public function testFieldNameConversion()
    {
        $builder = $this->dm->createAggregationBuilder(CmsComment::class);

        $builder->bucket()
            ->groupBy('$authorIp')
            ->boundaries(1, 2, 3)
            ->defaultBucket(0)
            ->output()
                ->field('averageValue')
                ->avg('$value');

        $this->assertEquals(
            [['$bucket' => [
                'groupBy' => '$ip',
                'boundaries' => [1, 2, 3],
                'default' => 0,
                'output' => ['averageValue' => ['$avg' => '$value']]
            ]]],
            $builder->getPipeline()
        );
    }
}
