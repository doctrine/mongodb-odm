<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional;

require_once __DIR__ . '/../../../../../TestInit.php';

class GeoSpacialTest extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    public function testSimple()
    {
        $this->dm->ensureDocumentIndexes(__NAMESPACE__.'\City');

        $city = new City();
        $city->name = 'Nashville';
        $city->coordinates = new Coordinates();
        $city->coordinates->latitude = 50;
        $city->coordinates->longitude = 30;

        $this->dm->persist($city);
        $this->dm->flush(array('safe' => true));
        $this->dm->clear();

        $city = $this->dm->createQuery(__NAMESPACE__.'\City')
            ->getSingleResult();
        $this->assertNotNull($city);
    }
}

/**
 * @Document
 * @Index(keys={"coordinates"="2d"})
 */
class City
{
    /** @Id */
    public $id;

    /** @String */
    public $name;

    /** @EmbedOne(targetDocument="Coordinates") */
    public $coordinates;

    /** @Distance */
    public $distance;
}

/** @EmbeddedDocument */
class Coordinates
{
    /** @Float */
    public $latitude;

    /** @Float */
    public $longitude;
}