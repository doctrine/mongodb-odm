<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Aggregation\Stage;

use Doctrine\ODM\MongoDB\Aggregation\Stage\Fill;
use Doctrine\ODM\MongoDB\Tests\Aggregation\AggregationTestTrait;
use Doctrine\ODM\MongoDB\Tests\BaseTest;

class FillTest extends BaseTest
{
    use AggregationTestTrait;

    public function testStage(): void
    {
        $builder   = $this->getTestAggregationBuilder();
        $fillStage = new Fill($builder);
        $fillStage
            ->partitionByFields('field1', 'field2')
            ->sortBy('field1', 1)
            ->output()
                ->field('foo')->locf()
                ->field('bar')->linear()
                ->field('fixed')->value(0)
                ->field('computed')->value(
                    $builder->expr()->multiply('$value', 5),
                );

        self::assertEquals(
            [
                '$fill' => (object) [
                    'partitionByFields' => ['field1', 'field2'],
                    'sortBy' => (object) ['field1' => 1],
                    'output' => (object) [
                        'foo' => ['method' => 'locf'],
                        'bar' => ['method' => 'linear'],
                        'fixed' => ['value' => 0],
                        'computed' => ['value' => ['$multiply' => ['$value', 5]]],
                    ],
                ],
            ],
            $fillStage->getExpression(),
        );
    }

    public function testStageWithComplexSort(): void
    {
        $fillStage = new Fill($this->getTestAggregationBuilder());
        $fillStage
            ->partitionByFields('field1', 'field2')
            ->sortBy(['field1' => 'asc', 'field2' => 'desc'])
            ->output()
                ->field('foo')->locf();

        self::assertEquals(
            [
                '$fill' => (object) [
                    'partitionByFields' => ['field1', 'field2'],
                    'sortBy' => (object) [
                        'field1' => 1,
                        'field2' => -1,
                    ],
                    'output' => (object) [
                        'foo' => ['method' => 'locf'],
                    ],
                ],
            ],
            $fillStage->getExpression(),
        );
    }

    public function testFromBuilder(): void
    {
        $builder = $this->getTestAggregationBuilder();
        $builder->fill()
            ->partitionBy('$field1')
            ->sortBy('field1', 1)
            ->output()
                ->field('foo')->locf()
                ->field('bar')->linear()
                ->field('fixed')->value(0);

        self::assertEquals(
            [
                [
                    '$fill' => (object) [
                        'partitionBy' => '$field1',
                        'sortBy' => (object) ['field1' => 1],
                        'output' => (object) [
                            'foo' => ['method' => 'locf'],
                            'bar' => ['method' => 'linear'],
                            'fixed' => ['value' => 0],
                        ],
                    ],
                ],
            ],
            $builder->getPipeline(),
        );
    }
}
