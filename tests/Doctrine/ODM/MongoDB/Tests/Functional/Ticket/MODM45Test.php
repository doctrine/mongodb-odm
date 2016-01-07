<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

class MODM45Test extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    public function testTest()
    {
        $a = new MODM45A();
        $a->setB(new MODM45B());

        $this->dm->persist($a);
        $this->dm->flush();
        $this->dm->clear();

        $a = $this->dm->find(__NAMESPACE__.'\MODM45A', $a->getId());
        $c = (null !== $a->getB()); 
        $this->assertTrue($c); // returns false, while expecting true
    }
}

/** @ODM\Document(collection="modm45_test") */
class MODM45A
{
    /** @ODM\Id */
    protected $id;

    /** @ODM\EmbedOne(targetDocument="MODM45B") */
    protected $b;

    function getId()  {return $this->id;}
    function getB()   {return $this->b;}
    function setB($b) {$this->b = $b;}
}

/** @ODM\EmbeddedDocument */
class MODM45B
{
    /** @ODM\Field(type="string") */
    protected $val;
    function setVal($val) {$this->val = $val;}
    function getVal() {return $this->val;}
}
