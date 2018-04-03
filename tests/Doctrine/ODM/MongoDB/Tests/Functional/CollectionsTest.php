<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Tests\BaseTest;
use Documents\Bars\Bar;
use Documents\Bars\Location;

class CollectionsTest extends BaseTest
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

        $bar = $this->dm->find(Bar::class, $bar->getId());

        $this->assertNotNull($bar);
        $locations = $bar->getLocations();
        unset($locations[0]);
        $locations[1]->setName('changed');
        $this->dm->flush();
        $this->dm->clear();

        $test = $this->dm->getDocumentCollection(Bar::class)->findOne();
        $this->assertCount(2, $test['locations']);

        $bar = $this->dm->find(Bar::class, $bar->getId());
        $this->assertNotNull($bar);
        $locations = $bar->getLocations();
        $this->assertCount(2, $locations);
        $this->assertEquals('changed', $locations[0]->getName());

        unset($locations[0], $locations[1]);
        $this->dm->flush();
        $this->dm->clear();

        $bar = $this->dm->find(Bar::class, $bar->getId());
        $this->assertNotNull($bar);
        $locations = $bar->getLocations();
        $this->assertCount(0, $locations);

        $bar->addLocation(new Location('West Nashville'));
        $bar->addLocation(new Location('East Nashville'));
        $bar->addLocation(new Location('North Nashville'));
        $this->dm->flush();
        $this->dm->clear();

        $bar = $this->dm->find(Bar::class, $bar->getId());
        $this->assertEquals($bar->getId(), $this->dm->getUnitOfWork()->getDocumentIdentifier($bar));

        $this->assertNotNull($bar);
        $locations = $bar->getLocations();
        $this->assertCount(3, $locations);
        $locations = $bar->getLocations();
        $locations->clear();
        $this->assertCount(0, $locations);
        $this->dm->flush();
        $this->dm->clear();
        $bar = $this->dm->find(Bar::class, $bar->getId());
        $locations = $bar->getLocations();
        $this->assertCount(0, $locations);
        $this->dm->flush();

        $bar->setLocations(new ArrayCollection([ new Location('Cracow') ]));
        $this->uow->computeChangeSets();
        $changeSet = $this->uow->getDocumentChangeSet($bar);
        $this->assertNotEmpty($changeSet['locations']);
        $this->assertSame($locations, $changeSet['locations'][0]);
        $this->assertSame($bar->getLocations(), $changeSet['locations'][1]);
    }

    public function testCreateCollectionsBasic()
    {
        $sm = $this->dm->getSchemaManager();
        $sm->dropDocumentCollection(CollectionTestBasic::class);
        $sm->createDocumentCollection(CollectionTestBasic::class);

        $coll = $this->dm->getDocumentCollection(CollectionTestBasic::class);
        $insert = [
            ['username' => 'bob'],
            ['username' => 'alice'],
            ['username' => 'jim'],
        ];
        $coll->insertMany($insert);

        $data = $coll->find()->toArray();
        $this->assertCount(3, $data);
    }

    public function testCreateCollectionsCapped()
    {
        $sm = $this->dm->getSchemaManager();
        $sm->dropDocumentCollection(CollectionTestCapped::class);
        $sm->createDocumentCollection(CollectionTestCapped::class);

        $coll = $this->dm->getDocumentCollection(CollectionTestCapped::class);
        $insert = [
            ['username' => 'bob'],
            ['username' => 'alice'],
            ['username' => 'jim'],
        ];
        $coll->insertMany($insert);

        $data = $coll->find()->toArray();
        $this->assertCount(1, $data);
    }
}

/**
 * @ODM\Document(collection={
 *   "name"="CollectionTestCapped",
 *   "capped"=true,
 *   "size"=1000,
 *   "max"=1
 * })
 */
class CollectionTestCapped
{
    /** @ODM\Id */
    public $id;

    /** @ODM\Field(type="string") */
    public $username;
}

/**
 * @ODM\Document
 */
class CollectionTestBasic
{
    /** @ODM\Id */
    public $id;

    /** @ODM\Field(type="string") */
    public $username;
}
