<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Aggregation\Stage;

use Doctrine\ODM\MongoDB\Aggregation\Stage\Densify;
use Doctrine\ODM\MongoDB\Tests\Aggregation\AggregationTestTrait;
use Doctrine\ODM\MongoDB\Tests\BaseTest;

class DensifyTest extends BaseTest
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

    public function testFromBuilder(): void
    {
        $builder = $this->getTestAggregationBuilder();
        $builder->densify('someField');

        self::assertEquals([['$densify' => (object) ['field' => 'someField']]], $builder->getPipeline());
    }
}
