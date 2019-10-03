<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Aggregation\Stage;

use Doctrine\ODM\MongoDB\Mapping\MappingException;
use Doctrine\ODM\MongoDB\Tests\BaseTest;
use Documents\Sharded\ShardedUser;
use Documents\SimpleReferenceUser;
use Documents\User;

class OutTest extends BaseTest
{
    public function testOutStageWithClassName()
    {
        $builder = $this->dm->createAggregationBuilder(SimpleReferenceUser::class);
        $builder
            ->out(User::class);

        $expectedPipeline = [
            ['$out' => 'users'],
        ];

        $this->assertEquals($expectedPipeline, $builder->getPipeline());
    }

    public function testOutStageWithCollectionName()
    {
        $builder = $this->dm->createAggregationBuilder(SimpleReferenceUser::class);
        $builder
            ->out('someRandomCollectionName');

        $expectedPipeline = [
            ['$out' => 'someRandomCollectionName'],
        ];

        $this->assertEquals($expectedPipeline, $builder->getPipeline());
    }

    public function testOutStageWithShardedClassName()
    {
        $builder = $this->dm->createAggregationBuilder(SimpleReferenceUser::class);
        $this->expectException(MappingException::class);
        $this->expectExceptionMessage('Cannot use class \'Documents\Sharded\ShardedUser\' as collection for out stage.');
        $builder->out(ShardedUser::class);
    }

    public function testSubsequentOutStagesAreOverwritten()
    {
        $builder = $this->dm->createAggregationBuilder(SimpleReferenceUser::class);
        $builder
            ->out('someCollection')
            ->out('otherCollection');

        $this->assertSame([['$out' => 'otherCollection']], $builder->getPipeline());
    }
}
