<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional;

require_once __DIR__ . '/../../../../../TestInit.php';

use Documents\Bars\Bar,
    Documents\Bars\Location;

class CollectionsTest extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    public function testCollections()
    {
        $bar = new Bar("Jon's Pub");
        $bar->addLocation(new Location('West Nashville'));
        $bar->addLocation(new Location('East Nashville'));
        $bar->addLocation(new Location('North Nashville'));
        $this->dm->persist($bar);
        $this->dm->flush();
        $this->dm->clear();

        $bar = $this->dm->findOne('Documents\Bars\Bar');

        $locations = $bar->getLocations();
        unset($locations[0]);
        $locations[1]->setName('changed');
        $this->dm->flush();
        $this->dm->clear();

        $test = $this->dm->getDocumentCollection('Documents\Bars\Bar')->findOne();
        $this->assertEquals(2, count($test['locations']));

        $bar = $this->dm->findOne('Documents\Bars\Bar');
        $this->assertNotNull($bar);
        $locations = $bar->getLocations();
        $this->assertEquals(2, count($locations));
        $this->assertEquals('changed', $locations[0]->getName());

        unset($locations[0], $locations[1]);
        $this->dm->flush();
        $this->dm->clear();

        $bar = $this->dm->findOne('Documents\Bars\Bar');
        $this->assertNotNull($bar);
        $locations = $bar->getLocations();
        $this->assertEquals(0, count($locations));

        $bar->addLocation(new Location('West Nashville'));
        $bar->addLocation(new Location('East Nashville'));
        $bar->addLocation(new Location('North Nashville'));
        $this->dm->flush();
        $this->dm->clear();

        $bar = $this->dm->findOne('Documents\Bars\Bar');
        $this->assertNotNull($bar);
        $locations = $bar->getLocations();
        $this->assertEquals(3, count($locations));
        $locations = $bar->getLocations();
        unset($locations[0], $locations[1], $locations[2]);
        $locations->clear();
        $this->assertEquals(0, count($locations));
        $this->dm->flush();
        $this->dm->clear();

        $bar = $this->dm->findOne('Documents\Bars\Bar');
        $locations = $bar->getLocations();
        $this->assertEquals(0, count($locations));
    }

    public function testCreateCollections()
    {
        $sm = $this->dm->getSchemaManager();
        $sm->dropDocumentCollection(__NAMESPACE__.'\CollectionTest');
        $sm->createDocumentCollection(__NAMESPACE__.'\CollectionTest');

        $coll = $this->dm->getMongo()->selectDB('colltest')->selectCollection('testing');
        $coll->batchInsert(array(array(1), array(2), array(3)), array('safe' => true));

        $data = iterator_to_array($coll->find());
        $this->assertEquals(1, count($data));
    }
}

/**
 * @Document(db="colltest", collection={
 *   "name"="testing",
 *   "capped"="true",
 *   "size"="1000",
 *   "max"="1"
 * })
 */
class CollectionTest
{
    /** @Id */
    public $id;

    /** @String */
    public $username;
}