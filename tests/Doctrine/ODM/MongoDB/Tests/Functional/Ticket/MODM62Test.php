<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

class MODM62Test extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    public function testTest()
    {
        $test = new MODM62Document();
        $this->dm->persist($test);
        $this->dm->flush();
        $this->dm->refresh($test);

        $test->setB(array('test', 'test2'));
        $this->dm->flush();
        $this->dm->clear();

        $test = $this->dm->find(__NAMESPACE__.'\MODM62Document', $test->id);
        $this->assertEquals(array('test', 'test2'), $test->b);
    }
}

/** @ODM\Document(collection="modm62_users") */
class MODM62Document
{
    /** @ODM\Id */
    public $id;

    /** @ODM\Field(type="collection") */
    public $b = array('ok');

    public function setB($b) {$this->b = $b;}
}
