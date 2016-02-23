<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

class MODM66Test extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{

    public function testTest()
    {
        $b1 = new MODM52B('first');
        $a = new MODM52A(array($b1));
        $this->dm->persist($a);
        $this->dm->flush();
        $b2 = new MODM52B('second');
        $a->getB()->add($b2);
        $this->dm->flush();

        $this->dm->refresh($a);
        $b = $a->getB()->toArray();

        $this->assertEquals(2, count($b));

        $this->assertEquals(array(
            $b1->getId(), $b2->getId()
            ), array(
            $b[0]->getId(), $b[1]->getId()
        ));
    }

    public function testRefresh()
    {
        $b1 = new MODM52B('first');
        $a = new MODM52A(array($b1));
        $this->dm->persist($a);
        $this->dm->flush();
        $b2 = new MODM52B('second');

        $this->dm->refresh($a);

        $a->getB()->add($b2);
        $this->dm->flush();
        $this->dm->refresh($a);
        $b = $a->getB()->toArray();

        $this->assertEquals(2, count($b));

        $this->assertEquals(array(
            $b1->getId(), $b2->getId()
            ), array(
            $b[0]->getId(), $b[1]->getId()
        ));
    }

}

/** @ODM\Document */
class MODM52A
{
    /** @ODM\Id */
    protected $id;

    /** @ODM\ReferenceMany(targetDocument="MODM52B", cascade="all") */
    protected $b;

    function __construct($b)
    {
        $this->b = new ArrayCollection($b);
    }

    function getB()
    {
        return $this->b;
    }
}

/** @ODM\Document */
class MODM52B
{

    /** @ODM\Id */
    protected $id;

    /** @ODM\Field(type="string") */
    protected $value;

    function __construct($v)
    {
        $this->value = $v;
    }

    public function getId()
    {
        return $this->id;
    }

}
