<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Aggregation\Stage;

use Doctrine\ODM\MongoDB\Aggregation\Builder;
use Doctrine\ODM\MongoDB\Tests\BaseTest;
use Documents\SimpleReferenceUser;
use Documents\User;
use Generator;

use function is_callable;

class MergeTest extends BaseTest
{
    public function testMergeStageWithClassName(): void
    {
        $builder = $this->dm->createAggregationBuilder(SimpleReferenceUser::class);
        $builder
            ->merge()
                ->into(User::class)
                ->on('_id')
                ->whenMatched('keepExisting');

        $expectedPipeline = [
            [
                '$merge' => (object) [
                    'into' => 'users',
                    'on' => '_id',
                    'whenMatched' => 'keepExisting',
                ],
            ],
        ];

        self::assertEquals($expectedPipeline, $builder->getPipeline());
    }

    public function testMergeStageWithCollectionName(): void
    {
        $builder = $this->dm->createAggregationBuilder(SimpleReferenceUser::class);
        $builder
            ->merge()
                ->into('someRandomCollectionName')
                ->let(['foo' => 'bar'])
                ->whenNotMatched('discard');

        $expectedPipeline = [
            [
                '$merge' => (object) [
                    'into' => 'someRandomCollectionName',
                    'let' => ['foo' => 'bar'],
                    'whenNotMatched' => 'discard',
                ],
            ],
        ];

        self::assertEquals($expectedPipeline, $builder->getPipeline());
    }

    public static function providePipeline(): Generator
    {
        // TODO: use $unset once it has been added
        yield 'Array' => [
            'pipeline' => [
                ['$set' => ['foo' => 'bar']],
                ['$project' => ['bar' => false]],
            ],
        ];

        yield 'Builder' => [
            'pipeline' => static function (Builder $builder): Builder {
                $builder
                    ->set()
                        ->field('foo')->expression('bar')
                    ->project()
                        ->excludeFields(['bar']);

                return $builder;
            },
        ];
    }

    /**
     * @param array<array<string, mixed>>|callable $pipeline
     *
     * @dataProvider providePipeline
     */
    public function testMergeStageWithPipeline($pipeline): void
    {
        if (is_callable($pipeline)) {
            $pipeline = $pipeline($this->dm->createAggregationBuilder(SimpleReferenceUser::class));
        }

        $builder = $this->dm->createAggregationBuilder(SimpleReferenceUser::class);
        $builder
            ->merge()
                ->into('someRandomCollectionName')
                ->whenMatched($pipeline);

        $expectedPipeline = [
            [
                '$merge' => (object) [
                    'into' => 'someRandomCollectionName',
                    'whenMatched' => [
                        ['$set' => ['foo' => 'bar']],
                        ['$project' => ['bar' => false]],
                    ],
                ],
            ],
        ];

        self::assertEquals($expectedPipeline, $builder->getPipeline());
    }
}
