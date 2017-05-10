<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

class MODM963Test extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{

    public function testTest()
    {
        $a = new A();
        $this->dm->persist($a);
        $this->dm->flush();

        $id = $a->getId();
        //Clear document manager is necesary to reproduce the bug.
        $this->dm->clear();

        $a = $this->dm->find(__NAMESPACE__.'\A', $id);
        $b2 = new B('second');
        $a->getB()->add($b2);
        $this->dm->flush();

        $this->assertEquals(1, count($a->getB()));
        //getIterator ( initialize() ) forces the error.
        $a->getB()->getIterator();
        $this->assertEquals(1, count($a->getB()));
    }
}

/** @ODM\Document */
class A
{
    /** @ODM\Id */
    protected $id;

    /** @ODM\ReferenceMany(targetDocument="B", mappedBy="a", cascade="all") */
    protected $b;

    function __construct()
    {
        $this->b = new ArrayCollection();
    }

    public function getId()
    {
        return $this->id;
    }

    function getB()
    {
        return $this->b;
    }
}

/** @ODM\Document */
class B
{

    /** @ODM\Id */
    protected $id;

    /** @ODM\String */
    protected $value;

    /**@ODM\ReferenceOne(targetDocument="A", inversedBy="b") */
    protected $a;

    function __construct($v)
    {
        $this->value = $v;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getValue()
    {
        return $this->value;
    }

}