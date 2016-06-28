<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional;

use Doctrine\Common\Collections\ArrayCollection;
use Documents\Bars\Bar;
use Documents\Bars\Location;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

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

        $bar = $this->dm->find('Documents\Bars\Bar', $bar->getId());

        $this->assertNotNull($bar);
        $locations = $bar->getLocations();
        unset($locations[0]);
        $locations[1]->setName('changed');
        $this->dm->flush();
        $this->dm->clear();

        $test = $this->dm->getDocumentCollection('Documents\Bars\Bar')->findOne();
        $this->assertEquals(2, count($test['locations']));

        $bar = $this->dm->find('Documents\Bars\Bar', $bar->getId());
        $this->assertNotNull($bar);
        $locations = $bar->getLocations();
        $this->assertEquals(2, count($locations));
        $this->assertEquals('changed', $locations[0]->getName());

        unset($locations[0], $locations[1]);
        $this->dm->flush();
        $this->dm->clear();

        $bar = $this->dm->find('Documents\Bars\Bar', $bar->getId());
        $this->assertNotNull($bar);
        $locations = $bar->getLocations();
        $this->assertEquals(0, count($locations));

        $bar->addLocation(new Location('West Nashville'));
        $bar->addLocation(new Location('East Nashville'));
        $bar->addLocation(new Location('North Nashville'));
        $this->dm->flush();
        $this->dm->clear();

        $bar = $this->dm->find('Documents\Bars\Bar', $bar->getId());
        $this->assertEquals($bar->getId(), $this->dm->getUnitOfWork()->getDocumentIdentifier($bar));

        $this->assertNotNull($bar);
        $locations = $bar->getLocations();
        $this->assertEquals(3, count($locations));
        $locations = $bar->getLocations();
        $locations->clear();
        $this->assertEquals(0, count($locations));
        $this->dm->flush();
        $this->dm->clear();
        $bar = $this->dm->find('Documents\Bars\Bar', $bar->getId());
        $locations = $bar->getLocations();
        $this->assertEquals(0, count($locations));
        $this->dm->flush();
        
        $bar->setLocations(new ArrayCollection([ new Location('Cracow') ]));
        $this->uow->computeChangeSets();
        $changeSet = $this->uow->getDocumentChangeSet($bar);
        $this->assertNotEmpty($changeSet['locations']);
        $this->assertSame($locations, $changeSet['locations'][0]);
        $this->assertSame($bar->getLocations(), $changeSet['locations'][1]);
    }

    public function testCreateCollections()
    {
        $sm = $this->dm->getSchemaManager();
        $sm->dropDocumentCollection(__NAMESPACE__.'\CreateCollectionTest');
        $sm->createDocumentCollection(__NAMESPACE__.'\CreateCollectionTest');

        $coll = $this->dm->getDocumentCollection(__NAMESPACE__.'\CreateCollectionTest');
        $insert = array(array(1), array(2), array(3));
        $coll->batchInsert($insert);

        $data = iterator_to_array($coll->find());
        $this->assertEquals(3, count($data));
    }
}

/**
 * @ODM\Document(collection={
 *   "name"="testing",
 *   "capped"="true",
 *   "size"="1000",
 *   "max"="1"
 * })
 */
class CollectionTest
{
    /** @ODM\Id */
    public $id;

    /** @ODM\Field(type="string") */
    public $username;
}

/**
 * @ODM\Document
 */
class CreateCollectionTest
{
    /** @ODM\Id */
    public $id;

}
