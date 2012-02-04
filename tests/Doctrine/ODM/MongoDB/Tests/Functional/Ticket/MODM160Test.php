<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Documents\Functional\Ticket\MODM160 as MODM160;

class MODM160Test extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    public function testEmbedManyInArrayMergeNew()
    {
        // create a test document
        $test = new MODM160\EmbedManyInArrayLevel0();
        $test->name = 'embedded test';

        $level1 = new MODM160\EmbedManyInArrayLevel1();
        $level1->name = 'test level1 #1';
        $test->level1[] = $level1;

        $level2 = new MODM160\MODM160Level2();
        $level2->name = 'test level2 #1';
        $level1->level2[] = $level2;

        $this->dm->merge($test);
    }

    public function testEmbedManyInArrayCollectionMergeNew()
    {
        // create a test document
        $test = new MODM160\EmbedManyInArrayCollectionLevel0();
        $test->name = 'embedded test';

        $level1 = new MODM160\EmbedManyInArrayCollectionLevel1();
        $level1->name = 'test level1 #1';
        $test->level1[] = $level1;

        $level2 = new MODM160\MODM160Level2();
        $level2->name = 'test level2 #1';
        $level1->level2[] = $level2;

        $this->dm->merge($test);
    }

    public function testEmbedOneMergeNew()
    {
        // create a test document
        $test = new MODM160\EmbedOneLevel0();
        $test->name = 'embedded test';

        $level1 = new MODM160\EmbedOneLevel1();
        $level1->name = 'test level1 #1';
        $test->level1 = $level1;

        $level2 = new MODM160\MODM160Level2();
        $level2->name = 'test level2 #1';
        $level1->level2 = $level2;

        $this->dm->merge($test);
    }
}