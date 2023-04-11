<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Aggregation\Stage;

use Doctrine\ODM\MongoDB\Aggregation\Builder;
use Doctrine\ODM\MongoDB\Tests\BaseTestCase;
use Documents\SimpleReferenceUser;
use Documents\User;
use Generator;
use InvalidArgumentException;

use function is_callable;

class MergeTest extends BaseTestCase
{
    public function testStageWithClassName(): void
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

    public function testStageWithCollectionName(): void
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
        yield 'Array' => [
            'pipeline' => [
                ['$set' => ['foo' => 'bar']],
                ['$unset' => ['bar']],
            ],
        ];

        yield 'Builder' => [
            'pipeline' => static function (Builder $builder): Builder {
                $builder
                    ->set()
                        ->field('foo')->expression('bar')
                    ->unset('bar');

                return $builder;
            },
        ];
    }

    /**
     * @param array<array<string, mixed>>|callable $pipeline
     *
     * @dataProvider providePipeline
     */
    public function testStageWithPipeline($pipeline): void
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
                        ['$unset' => ['bar']],
                    ],
                ],
            ],
        ];

        self::assertEquals($expectedPipeline, $builder->getPipeline());
    }

    public function testStageWithReusedPipeline(): void
    {
        $builder  = $this->dm->createAggregationBuilder(SimpleReferenceUser::class);
        $setStage = $builder->set()
            ->field('foo')->expression('bar');

        self::expectException(InvalidArgumentException::class);
        self::expectExceptionMessage('Cannot use the same Builder instance for $merge whenMatched pipeline.');

        $builder
            ->merge()
                ->into('someRandomCollectionName')
                ->whenMatched($setStage);
    }
}
