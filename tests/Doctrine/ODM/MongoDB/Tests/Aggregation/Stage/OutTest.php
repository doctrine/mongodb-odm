<?php

namespace Doctrine\ODM\MongoDB\Tests\Aggregation\Stage;

class OutTest extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    public function testOutStageWithClassName()
    {
        $builder = $this->dm->createAggregationBuilder(\Documents\SimpleReferenceUser::class);
        $builder
            ->out(\Documents\User::class);

        $expectedPipeline = [
            [
                '$out' => 'users'
            ]
        ];

        $this->assertEquals($expectedPipeline, $builder->getPipeline());
    }

    public function testOutStageWithCollectionName()
    {
        $builder = $this->dm->createAggregationBuilder(\Documents\SimpleReferenceUser::class);
        $builder
            ->out('someRandomCollectionName');

        $expectedPipeline = [
            [
                '$out' => 'someRandomCollectionName'
            ]
        ];

        $this->assertEquals($expectedPipeline, $builder->getPipeline());
    }

    /**
     * @expectedException \Doctrine\ODM\MongoDB\Mapping\MappingException
     * @expectedExceptionMessage Cannot use class 'Documents\Sharded\ShardedUser' as collection for out stage.
     */
    public function testOutStageWithShardedClassName()
    {
        $builder = $this->dm->createAggregationBuilder(\Documents\SimpleReferenceUser::class);
        $builder
            ->out(\Documents\Sharded\ShardedUser::class);

        $builder->getPipeline();
    }
}
