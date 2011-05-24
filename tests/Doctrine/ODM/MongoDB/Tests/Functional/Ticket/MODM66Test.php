<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

class MODM66Test extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{

    public function testTest()
    {
        $b1 = new B('first');
        $a = new A(array($b1));
        $this->dm->persist($a);
        $this->dm->flush();
        $b2 = new B('second');
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
        $b1 = new B('first');
        $a = new A(array($b1));
        $this->dm->persist($a);
        $this->dm->flush();
        $b2 = new B('second');

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

/** @ODM\Document(db="tests", collection="tests") */
class A
{
    /** @ODM\Id */
    protected $id;

    /** @ODM\ReferenceMany(targetDocument="b", cascade="all") */
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

/** @ODM\Document(db="tests", collection="tests2") */
class B
{

    /** @ODM\Id */
    protected $id;

    /** @ODM\String */
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