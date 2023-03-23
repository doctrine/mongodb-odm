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
    public function testStageWithClassName(): void
    {
        $builder = $this->dm->createAggregationBuilder(SimpleReferenceUser::class);
        $builder
            ->out(User::class);

        $expectedPipeline = [
            ['$out' => 'users'],
        ];

        self::assertEquals($expectedPipeline, $builder->getPipeline());
    }

    public function testStageWithCollectionName(): void
    {
        $builder = $this->dm->createAggregationBuilder(SimpleReferenceUser::class);
        $builder
            ->out('someRandomCollectionName');

        $expectedPipeline = [
            ['$out' => 'someRandomCollectionName'],
        ];

        self::assertEquals($expectedPipeline, $builder->getPipeline());
    }

    public function testStageWithShardedClassName(): void
    {
        $builder = $this->dm->createAggregationBuilder(SimpleReferenceUser::class);
        $this->expectException(MappingException::class);
        $this->expectExceptionMessage('Cannot use class \'Documents\Sharded\ShardedUser\' as collection for out stage.');
        $builder->out(ShardedUser::class);
    }

    public function testSubsequentOutStagesAreOverwritten(): void
    {
        $builder = $this->dm->createAggregationBuilder(SimpleReferenceUser::class);
        $builder
            ->out('someCollection')
            ->out('otherCollection');

        self::assertSame([['$out' => 'otherCollection']], $builder->getPipeline());
    }
}
