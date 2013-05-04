<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Documents\Functional\Building;
use Documents\Functional\House;

class NoDiscriminatorMapTest extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    public function testFlushAndFindWithoutDefinedDiscriminatorMap()
    {
        $building = new Building();
        $building->setAddress('Kharkov, Svetlaya st., 1');
        $this->dm->persist($building);

        $house = new House();
        $house->setAddress('Kharkov, Malaya Danilovka, 35');
        $house->setOwnerName('Viktor Leshenko');
        $this->dm->persist($house);

        $this->dm->flush();

        $buildings = $this->dm->getRepository('Documents\Functional\Building')->findAll();
        $this->assertCount(2, $buildings);

        $houses = $this->dm->getRepository('Documents\Functional\House')->findAll();
        $this->assertCount(1, $houses);
    }

    public function testReferenceOneWithoutDiscriminatorMapInheritanceProxy()
    {
        $livingBuilding = new \Documents\Functional\Cottage();
        $developer = new \Documents\Developer('avalanche123');
        $developer->setLivingBuilding($livingBuilding);

        $this->dm->persist($developer);
        $this->dm->flush();
        $this->dm->clear();

        /** @var \Documents\Developer $developer */
        $developer = $this->dm->find('Documents\Developer', $developer->getId());
        $livingBuilding = $developer->getLivingBuilding();

        $this->assertInstanceOf('Documents\Functional\Cottage', $livingBuilding);
    }

    public function testReferenceManyWithoutDiscriminatorMapInheritanceProxy()
    {
        $favouriteWarehouse = new \Documents\Functional\Warehouse();
        $justSomeVisitedBuilding = new \Documents\Functional\Building();
        $livingBuilding = new \Documents\Functional\Cottage();
        $developer = new \Documents\Developer('avalanche123');
        $visitedBuildings = $developer->getVisitedBuildings();
        $visitedBuildings->add($favouriteWarehouse);
        $visitedBuildings->add($justSomeVisitedBuilding);
        $visitedBuildings->add($livingBuilding);

        $this->dm->persist($developer);
        $this->dm->flush();
        $this->dm->clear();

        /** @var \Documents\Developer $developer */
        $developer = $this->dm->find('Documents\Developer', $developer->getId());
        $visitedBuildings = $developer->getVisitedBuildings();

        foreach ($visitedBuildings as $building) {

        }

        $this->assertInstanceOf('Documents\Functional\Warehouse', $visitedBuildings[0]);
        $this->assertInstanceOf('Documents\Functional\Building', $visitedBuildings[1]);
        $this->assertInstanceOf('Documents\Functional\Cottage', $visitedBuildings[2]);
    }

}
