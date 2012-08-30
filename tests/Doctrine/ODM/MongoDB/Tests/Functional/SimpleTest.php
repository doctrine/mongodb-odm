<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional;

use Documents\Bars\Bar;
use Documents\Bars\Location;

class SimpleTest extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    public function testSimple()
    {
        $bar = new Bar("Jon's Pub");
        $bar->addLocation(new Location('West Nashville'));
        $bar->addLocation(new Location('East Nashville'));
        $bar->addLocation(new Location('North Nashville'));
        $this->dm->persist($bar);
        $this->dm->flush();
        $this->dm->clear();

        $bar = $this->dm->find('Documents\Bars\Bar', $bar->getId());

        $locations = $bar->getLocations();
        unset($locations[0]);

        $this->dm->flush();

        $test = $this->dm->getDocumentCollection('Documents\Bars\Bar')->findOne();
        $this->assertEquals(2, count($test['locations']));
    }
}