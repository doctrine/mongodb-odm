<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Aggregation\Stage;

use Doctrine\ODM\MongoDB\Aggregation\Stage\SetWindowFields;
use Doctrine\ODM\MongoDB\Tests\Aggregation\AggregationOperatorsProviderTrait;
use Doctrine\ODM\MongoDB\Tests\Aggregation\AggregationTestTrait;
use Doctrine\ODM\MongoDB\Tests\BaseTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

use function array_merge;

class SetWindowFieldsTest extends BaseTestCase
{
    use AggregationTestTrait;
    use AggregationOperatorsProviderTrait;

    public function testStage(): void
    {
        $builder              = $this->getTestAggregationBuilder();
        $setWindowFieldsStage = new SetWindowFields($builder);
        $setWindowFieldsStage
            ->partitionBy($builder->expr()->year('field1'))
            ->sortBy('field1', 1)
            ->output()
                ->field('foo')->locf('$foo')
                ->field('bar')->linearFill('$bar')
                ->field('previous')->shift('$$ROOT', -1)
                ->field('documentsSum')
                    ->sum('$amount')
                    ->window(['unbounded', 'current'])
                ->field('rangeSum')
                    ->sum('$amount')
                    ->window(null, [-1, 1])
                ->field('rangeSumWithUnit')
                    ->sum('$amount')
                    ->window(null, ['current', 'unbounded'], 'second');

        self::assertEquals(
            [
                '$setWindowFields' => (object) [
                    'partitionBy' => ['$year' => 'field1'],
                    'sortBy' => (object) ['field1' => 1],
                    'output' => (object) [
                        'foo' => ['$locf' => '$foo'],
                        'bar' => ['$linearFill' => '$bar'],
                        'previous' => [
                            '$shift' => [
                                'output' => '$$ROOT',
                                'by' => -1,
                            ],
                        ],
                        'documentsSum' => [
                            '$sum' => '$amount',
                            'window' => (object) ['documents' => ['unbounded', 'current']],
                        ],
                        'rangeSum' => [
                            '$sum' => '$amount',
                            'window' => (object) ['range' => [-1, 1]],
                        ],
                        'rangeSumWithUnit' => [
                            '$sum' => '$amount',
                            'window' => (object) [
                                'range' => ['current', 'unbounded'],
                                'unit' => 'second',
                            ],
                        ],
                    ],
                ],
            ],
            $setWindowFieldsStage->getExpression(),
        );
    }

    #[DataProvider('provideGroupAccumulatorExpressionOperators')]
    #[DataProvider('provideWindowExpressionOperators')]
    public function testOperators(array $expected, string $operator, $args): void
    {
        $args = $this->resolveArgs($args);

        $builder              = $this->getTestAggregationBuilder();
        $setWindowFieldsStage = new SetWindowFields($builder);
        $setWindowFieldsStage
            ->partitionBy($builder->expr()->year('field1'))
            ->sortBy('field1', 1)
            ->output()
                ->field('foo')
                ->$operator(...$args)
                ->window(['unbounded', 'current']);

        self::assertEquals(
            [
                '$setWindowFields' => (object) [
                    'partitionBy' => ['$year' => 'field1'],
                    'sortBy' => (object) ['field1' => 1],
                    'output' => (object) [
                        'foo' => array_merge(
                            $expected,
                            ['window' => (object) ['documents' => ['unbounded', 'current']]],
                        ),
                    ],
                ],
            ],
            $setWindowFieldsStage->getExpression(),
        );
    }

    public function testStageWithComplexSort(): void
    {
        $setWindowFieldsStage = new SetWindowFields($this->getTestAggregationBuilder());
        $setWindowFieldsStage
            ->partitionBy('$field1')
            ->sortBy(['field1' => 'asc', 'field2' => 'desc'])
            ->output()
                ->field('foo')->locf('$foo');

        self::assertEquals(
            [
                '$setWindowFields' => (object) [
                    'partitionBy' => '$field1',
                    'sortBy' => (object) [
                        'field1' => 1,
                        'field2' => -1,
                    ],
                    'output' => (object) [
                        'foo' => ['$locf' => '$foo'],
                    ],
                ],
            ],
            $setWindowFieldsStage->getExpression(),
        );
    }

    public function testFromBuilder(): void
    {
        $builder = $this->getTestAggregationBuilder();
        $builder->setWindowFields()
            ->partitionBy('$field1')
            ->sortBy('field1', 1)
            ->output()
                ->field('foo')->locf('$foo');

        self::assertEquals(
            [
                [
                    '$setWindowFields' => (object) [
                        'partitionBy' => '$field1',
                        'sortBy' => (object) ['field1' => 1],
                        'output' => (object) [
                            'foo' => ['$locf' => '$foo'],
                        ],
                    ],
                ],
            ],
            $builder->getPipeline(),
        );
    }
}
