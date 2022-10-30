<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\ODM\MongoDB\Tests\BaseTest;
use Documents\Functional\Ticket\MODM160;

class MODM160Test extends BaseTest
{
    /** @doesNotPerformAssertions */
    public function testEmbedManyInArrayMergeNew(): void
    {
        // create a test document
        $test       = new MODM160\EmbedManyInArrayLevel0();
        $test->name = 'embedded test';

        $level1         = new MODM160\EmbedManyInArrayLevel1();
        $level1->name   = 'test level1 #1';
        $test->level1[] = $level1;

        $level2           = new MODM160\MODM160Level2();
        $level2->name     = 'test level2 #1';
        $level1->level2[] = $level2;

        $this->dm->merge($test);
    }

    /** @doesNotPerformAssertions */
    public function testEmbedManyInArrayCollectionMergeNew(): void
    {
        // create a test document
        $test       = new MODM160\EmbedManyInArrayCollectionLevel0();
        $test->name = 'embedded test';

        $level1         = new MODM160\EmbedManyInArrayCollectionLevel1();
        $level1->name   = 'test level1 #1';
        $test->level1[] = $level1;

        $level2           = new MODM160\MODM160Level2();
        $level2->name     = 'test level2 #1';
        $level1->level2[] = $level2;

        $this->dm->merge($test);
    }

    /** @doesNotPerformAssertions */
    public function testEmbedOneMergeNew(): void
    {
        // create a test document
        $test       = new MODM160\EmbedOneLevel0();
        $test->name = 'embedded test';

        $level1       = new MODM160\EmbedOneLevel1();
        $level1->name = 'test level1 #1';
        $test->level1 = $level1;

        $level2         = new MODM160\MODM160Level2();
        $level2->name   = 'test level2 #1';
        $level1->level2 = $level2;

        $this->dm->merge($test);
    }
}
