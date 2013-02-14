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

}
