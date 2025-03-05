<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional;

use Doctrine\ODM\MongoDB\Tests\BaseTestCase;
use Documents\Place;

class GeoTest extends BaseTestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $this->dm->getSchemaManager()->ensureDocumentIndexes(Place::class);
        $this->dm->persist(new Place(
            name: 'Central Park',
            location: ['type' => 'Point', 'coordinates' => [-73.97, 40.77]],
            category: 'Parks',
        ));
        $this->dm->persist(new Place(
            name: 'Sara D. Roosevelt Park',
            location: ['type' => 'Point', 'coordinates' => [-73.9928, 40.7193]],
            category: 'Parks',
        ));
        $this->dm->persist(new Place(
            name: 'Polo Grounds',
            location: ['type' => 'Point', 'coordinates' => [-73.9928, 40.7193]],
            category: 'Stadiums',
        ));
        $this->dm->flush();
    }

    public function testGeoNearWithQuery(): void
    {
        $pipeline = $this->dm->createAggregationBuilder(Place::class)
            ->geoNear(-73.99279, 40.719296)
                ->field('category')->equals('Parks')
                ->distanceField('calculated')
                ->maxDistance(2)
                ->spherical(true)
            ->getAggregation();

        $results = $pipeline->execute()->toArray();
        self::assertCount(2, $results);
        self::assertSame('Sara D. Roosevelt Park', $results[0]['name']);
        self::assertIsFloat($results[0]['calculated']);
    }

    public function testGeoNearWithEmptyQuery(): void
    {
        $pipeline = $this->dm->createAggregationBuilder(Place::class)
            ->geoNear(-73.99279, 40.719296)
                ->distanceField('dist.calculated')
                ->maxDistance(2)
                ->includeLocs('dist.location')
                ->spherical(true)
            ->getAggregation();

        $results = $pipeline->execute()->toArray();
        self::assertCount(3, $results);
        self::assertSame('Sara D. Roosevelt Park', $results[0]['name']);
        self::assertIsArray($results[0]['dist']);
        self::assertIsArray($results[0]['dist']['location']);
        self::assertIsFloat($results[0]['dist']['calculated']);
    }
}
