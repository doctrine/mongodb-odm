<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

class GeoSpacialTest extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    public function testQueries()
    {
        $qb = $this->dm->createQueryBuilder(__NAMESPACE__.'\City')
            ->field('latitude')->near(1000000)
            ->field('longitude')->near(11111);
        $this->assertEquals(array('latitude' => 1000000, 'longitude' => 11111), $qb->debug('near'));

        $qb = $this->dm->createQueryBuilder(__NAMESPACE__.'\City')
            ->field('coordinates')->withinBox(41, 41, 72, 72);
        $this->assertEquals(array(
            'coordinates' => array(
                '$within' => array('$box' => array(array(41, 41), array(72, 72)))
            )
        ), $qb->getQueryArray());
    }

    public function testGetFieldsInCoordinatesQuery()
    {
        $qb = $this->dm->createQueryBuilder(__NAMESPACE__.'\City');
        $qb->field('coordinates')->withinBox(41, 41, 72, 72);
        $query = $qb->getQuery();
        $this->assertEquals(array('coordinates'), $query->getFieldsInQuery());
    }

    public function testGeoSpatial1()
    {
        $this->dm->getSchemaManager()->ensureDocumentIndexes(__NAMESPACE__.'\City');

        $city = new City();
        $city->name = 'Nashville';
        $city->coordinates = new Coordinates();
        $city->coordinates->latitude = 50;
        $city->coordinates->longitude = 30;

        $this->dm->persist($city);
        $this->dm->flush(null, array('safe' => true));
        $this->dm->clear();

        $qb = $this->dm->createQueryBuilder(__NAMESPACE__.'\City')
            ->field('latitude')->near(1000000)
            ->field('longitude')->near(11111);
        $query = $qb->getQuery();
        $city = $query->getSingleResult();
        $this->assertNull($city);

        $city = $this->dm->createQueryBuilder(__NAMESPACE__.'\City')
            ->field('latitude')->near(50)
            ->field('longitude')->near(50)
            ->getQuery()
            ->getSingleResult();
        $this->assertNotNull($city);

        $this->assertEquals(20, round($city->test));

        $query = $this->dm->createQueryBuilder(__NAMESPACE__.'\City')
            ->field('coordinates')->near(50, 50)
            ->getQuery();
        foreach ($query as $city2) {
            $this->assertEquals($city, $city2);
        }
    }

    public function testGeoSpatial2()
    {
        $this->dm->getSchemaManager()->ensureDocumentIndexes(__NAMESPACE__.'\City');

        $city = new City();
        $city->name = 'Nashville';
        $city->coordinates = new Coordinates();
        $city->coordinates->latitude = 34.2055968;
        $city->coordinates->longitude = -118.8713314;

        $this->dm->persist($city);
        $this->dm->flush(null, array('safe' => true));
        $this->dm->clear();

        $city = $this->dm->createQueryBuilder(__NAMESPACE__.'\City')
            ->field('coordinates.latitude')->near(50)
            ->field('coordinates.longitude')->near(50)
            ->getQuery()
            ->getSingleResult();
        $this->assertNotNull($city);
    }

    public function testWithinBox()
    {
        $this->dm->getSchemaManager()->ensureDocumentIndexes(__NAMESPACE__.'\City');

        $city = new City();
        $city->name = 'Nashville';
        $city->coordinates = new Coordinates();
        $city->coordinates->latitude = 40.739037;
        $city->coordinates->longitude = 73.992964;

        $this->dm->persist($city);
        $this->dm->flush(null, array('safe' => true));
        $this->dm->clear();

        $city = $this->dm->createQueryBuilder(__NAMESPACE__.'\City')
            ->field('coordinates')->withinBox(41, 41, 72, 72)
            ->getQuery()
            ->getSingleResult();
        $this->assertNull($city);

        $city = $this->dm->createQueryBuilder(__NAMESPACE__.'\City')
            ->field('coordinates')->withinBox(30, 30, 80, 80)
            ->field('name')->equals('Nashville')
            ->getQuery()
            ->getSingleResult();
        $this->assertNotNull($city);
    }

    public function testWithinCenter()
    {
        $this->dm->getSchemaManager()->ensureDocumentIndexes(__NAMESPACE__.'\City');

        $city = new City();
        $city->name = 'Nashville';
        $city->coordinates = new Coordinates();
        $city->coordinates->latitude = 50;
        $city->coordinates->longitude = 30;

        $this->dm->persist($city);
        $this->dm->flush(null, array('safe' => true));
        $this->dm->clear();

        $city = $this->dm->createQueryBuilder(__NAMESPACE__.'\City')
            ->field('coordinates')->withinCenter(50, 50, 20)
            ->field('name')->equals('Nashville')
            ->getQuery()
            ->getSingleResult();
        $this->assertNotNull($city);
    }
}

/**
 * @ODM\Document
 * @ODM\Index(keys={"coordinates"="2d"})
 */
class City
{
    /** @ODM\Id */
    public $id;

    /** @ODM\String */
    public $name;

    /** @ODM\EmbedOne(targetDocument="Coordinates") */
    public $coordinates;

    /** @ODM\Distance */
    public $test;
}

/** @ODM\EmbeddedDocument */
class Coordinates
{
    /** @ODM\Float */
    public $latitude;

    /** @ODM\Float */
    public $longitude;
}