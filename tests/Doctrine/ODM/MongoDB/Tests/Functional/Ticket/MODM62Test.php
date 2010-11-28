<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

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

/** @Document(collection="modm62_users") */
class MODM62Document
{
    /** @Id */
    public $id;

    /** @Collection */
    public $b = array('ok');

    public function setB($b) {$this->b = $b;}
}