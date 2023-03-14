<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Aggregation\Stage;

use Doctrine\ODM\MongoDB\Aggregation\Stage\Fill;
use Doctrine\ODM\MongoDB\Tests\Aggregation\AggregationTestTrait;
use Doctrine\ODM\MongoDB\Tests\BaseTest;

class FillTest extends BaseTest
{
    use AggregationTestTrait;

    public function testFillStage(): void
    {
        $fillStage = new Fill($this->getTestAggregationBuilder());
        $fillStage
            ->partitionByFields('field1', 'field2')
            ->sortBy('field1', 1)
            ->output()
                ->field('foo')->locf()
                ->field('bar')->linear()
                ->field('fixed')->value(0);

        self::assertEquals(
            [
                '$fill' => (object) [
                    'partitionByFields' => ['field1', 'field2'],
                    'sortBy' => (object) ['field1' => 1],
                    'output' => (object) [
                        'foo' => ['method' => 'locf'],
                        'bar' => ['method' => 'linear'],
                        'fixed' => ['value' => 0],
                    ],
                ],
            ],
            $fillStage->getExpression(),
        );
    }

    public function testCountFromBuilder(): void
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
