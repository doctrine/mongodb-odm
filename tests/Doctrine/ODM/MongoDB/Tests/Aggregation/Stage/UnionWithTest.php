<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Aggregation\Stage;

use Doctrine\ODM\MongoDB\Tests\BaseTestCase;
use Documents\SimpleReferenceUser;
use Documents\User;

class UnionWithTest extends BaseTestCase
{
    public function testStageWithClassName(): void
    {
        $builder = $this->dm->createAggregationBuilder(SimpleReferenceUser::class);
        $builder
            ->unionWith(User::class);

        $expectedPipeline = [
            ['$unionWith' => (object) ['coll' => 'users']],
        ];

        self::assertEquals($expectedPipeline, $builder->getPipeline());
    }

    public function testStageWithCollectionName(): void
    {
        $unionBuilder = $this->dm->createAggregationBuilder(SimpleReferenceUser::class);
        $unionBuilder->match()
            ->field('foo')->equals('bar');

        $builder = $this->dm->createAggregationBuilder(SimpleReferenceUser::class);
        $builder
            ->unionWith('someRandomCollectionName')
                ->pipeline($unionBuilder);

        $expectedPipeline = [
            [
                '$unionWith' => (object) [
                    'coll' => 'someRandomCollectionName',
                    'pipeline' => [
                        ['$match' => ['foo' => 'bar']],
                    ],
                ],
            ],
        ];

        self::assertEquals($expectedPipeline, $builder->getPipeline());
    }
}
