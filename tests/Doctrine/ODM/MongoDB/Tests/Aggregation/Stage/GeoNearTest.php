<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Aggregation\Stage;

use Doctrine\ODM\MongoDB\Aggregation\Stage\GeoNear;
use Doctrine\ODM\MongoDB\Tests\Aggregation\AggregationTestTrait;
use Doctrine\ODM\MongoDB\Tests\BaseTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use stdClass;

class GeoNearTest extends BaseTestCase
{
    use AggregationTestTrait;

    public function testStage(): void
    {
        $geoNearStage = new GeoNear($this->getTestAggregationBuilder(), 0, 0);
        $geoNearStage
            ->distanceField('distance')
            ->field('someField')
            ->equals('someValue');

        $stage = ['near' => [0, 0], 'spherical' => false, 'distanceField' => 'distance', 'query' => ['someField' => 'someValue']];
        self::assertSame(['$geoNear' => $stage], $geoNearStage->getExpression());
    }

    public function testFromBuilder(): void
    {
        $builder = $this->getTestAggregationBuilder();
        $builder
            ->geoNear(0, 0)
            ->distanceField('distance')
            ->field('someField')
            ->equals('someValue');

        $stage = ['near' => [0, 0], 'spherical' => false, 'distanceField' => 'distance', 'query' => ['someField' => 'someValue']];
        self::assertSame([['$geoNear' => $stage]], $builder->getPipeline());
    }

    /** @param mixed $value */
    #[DataProvider('provideOptionalSettings')]
    public function testOptionalSettings(string $field, $value): void
    {
        $geoNearStage = new GeoNear($this->getTestAggregationBuilder(), 0, 0);

        $pipeline = $geoNearStage->getExpression();
        self::assertArrayNotHasKey($field, $pipeline['$geoNear']);

        $geoNearStage->$field($value);
        $pipeline = $geoNearStage->getExpression();

        self::assertSame($value, $pipeline['$geoNear'][$field]);
    }

    public static function provideOptionalSettings(): array
    {
        return [
            'distanceMultiplier' => ['distanceMultiplier', 15.0],
            'includeLocs' => ['includeLocs', 'dist.location'],
            'maxDistance' => ['maxDistance', 15.0],
            'minDistance' => ['minDistance', 15.0],
            'num' => ['num', 15],
            'uniqueDocs' => ['uniqueDocs', true],
        ];
    }

    public function testLimitDoesNotCreateExtraStage(): void
    {
        $builder = $this->getTestAggregationBuilder();
        $builder
            ->geoNear(0, 0)
            ->limit(1);

        $stage = $builder->getPipeline()[0];
        self::assertSame([0, 0], $stage['$geoNear']['near']);
        self::assertFalse($stage['$geoNear']['spherical']);
        self::assertNull($stage['$geoNear']['distanceField']);
        self::assertEquals(new stdClass(), $stage['$geoNear']['query']);
        self::assertSame(1, $stage['$geoNear']['num']);
    }
}
