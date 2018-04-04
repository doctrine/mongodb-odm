<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Tests\BaseTest;

class MODM62Test extends BaseTest
{
    public function testTest()
    {
        $test = new MODM62Document();
        $this->dm->persist($test);
        $this->dm->flush();
        $this->dm->refresh($test);

        $test->setB(['test', 'test2']);
        $this->dm->flush();
        $this->dm->clear();

        $test = $this->dm->find(MODM62Document::class, $test->id);
        $this->assertEquals(['test', 'test2'], $test->b);
    }
}

/** @ODM\Document(collection="modm62_users") */
class MODM62Document
{
    /** @ODM\Id */
    public $id;

    /** @ODM\Field(type="collection") */
    public $b = ['ok'];

    public function setB($b)
    {
        $this->b = $b;
    }
}
