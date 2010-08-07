<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional;

require_once __DIR__ . '/../../../../../TestInit.php';

use Documents\Bars\Bar,
    Documents\Bars\Location;

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

        $bar = $this->dm->findOne('Documents\Bars\Bar');

        $locations = $bar->getLocations();
        unset($locations[0]);

        $this->dm->flush();

    }
}