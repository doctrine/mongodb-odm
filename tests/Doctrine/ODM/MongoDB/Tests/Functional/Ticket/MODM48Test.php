<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

class MODM48Test extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    public function testTest()
    {
        $a = new MODM48A();
        $a->b = new MODM48B();
        $this->dm->persist($a);
        $this->dm->flush();
        $this->dm->clear();

        $a = $this->dm->find(__NAMESPACE__.'\MODM48A', $a->id);
        $this->assertNotNull($a);

        $a->getB()->setVal('test');

        $this->dm->flush();
        $this->dm->clear();

        $a = $this->dm->find(__NAMESPACE__.'\MODM48A', $a->id);
        $this->assertEquals('test', $a->getB()->getVal());
    }
}

/** @ODM\Document */
class MODM48A
{
    /** @ODM\Id */
    public $id;

    /** @ODM\EmbedOne(targetDocument="MODM48B") */
    public $b;

    function getId()  {return $this->id;}
    function getB()   {return $this->b;}
    function setB($b) {$this->b = $b;}
}

/** @ODM\EmbeddedDocument */
class MODM48B
{
    /** @ODM\Field(type="string") */
    public $val;

    function setVal($val) {$this->val = $val;}
    function getVal() {return $this->val;}
}
