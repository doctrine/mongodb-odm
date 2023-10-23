<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Aggregation\Stage;

use Doctrine\ODM\MongoDB\Aggregation\Stage\Densify;
use Doctrine\ODM\MongoDB\Tests\Aggregation\AggregationTestTrait;
use Doctrine\ODM\MongoDB\Tests\BaseTestCase;

class DensifyTest extends BaseTestCase
{
    use AggregationTestTrait;

    public function testStage(): void
    {
        $densifyStage = new Densify($this->getTestAggregationBuilder(), 'someField');
        $densifyStage
            ->partitionByFields('field1', 'field2')
            ->range('full', 1);

        self::assertEquals(
            [
                '$densify' => (object) [
                    'field' => 'someField',
                    'partitionByFields' => ['field1', 'field2'],
                    'range' => (object) [
                        'bounds' => 'full',
                        'step' => 1,
                    ],
                ],
            ],
            $densifyStage->getExpression(),
        );
    }

    public function testStageWithPartialBounds(): void
    {
        $densifyStage = new Densify($this->getTestAggregationBuilder(), 'someField');
        $densifyStage
            ->partitionByFields('field1', 'field2')
            ->range([1.5, 2.5], 0.1);

        self::assertEquals(
            [
                '$densify' => (object) [
                    'field' => 'someField',
                    'partitionByFields' => ['field1', 'field2'],
                    'range' => (object) [
                        'bounds' => [1.5, 2.5],
                        'step' => 0.1,
                    ],
                ],
            ],
            $densifyStage->getExpression(),
        );
    }

    public function testStageWithRangeUnit(): void
    {
        $densifyStage = new Densify($this->getTestAggregationBuilder(), 'someField');
        $densifyStage
            ->partitionByFields('field1', 'field2')
            ->range('full', 1, 'minute');

        self::assertEquals(
            [
                '$densify' => (object) [
                    'field' => 'someField',
                    'partitionByFields' => ['field1', 'field2'],
                    'range' => (object) [
                        'bounds' => 'full',
                        'step' => 1,
                        'unit' => 'minute',
                    ],
                ],
            ],
            $densifyStage->getExpression(),
        );
    }

    public function testFromBuilder(): void
    {
        $builder = $this->getTestAggregationBuilder();
        $builder
            ->densify('someField')
            ->range('full', 1, 'minute');

        self::assertEquals(
            [
                [
                    '$densify' => (object) [
                        'field' => 'someField',
                        'range' => (object) [
                            'bounds' => 'full',
                            'step' => 1,
                            'unit' => 'minute',
                        ],
                    ],
                ],
            ],
            $builder->getPipeline(),
        );
    }
}
