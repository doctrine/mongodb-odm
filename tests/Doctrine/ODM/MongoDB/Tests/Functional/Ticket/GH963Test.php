<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

class GH963Test extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    public function testTest()
    {
        $a = new GH963A();
        $this->dm->persist($a);
        $this->dm->flush();

        $id = $a->getId();
        //Clear document manager is necesary to reproduce the bug.
        $this->dm->clear();

        $a = $this->dm->find(__NAMESPACE__.'\GH963A', $id);
        $b2 = new GH963B('second');
        $a->getB()->add($b2);
        $this->dm->flush();

        $this->assertEquals(1, count($a->getB()));
        //getIterator ( initialize() ) forces the error.
        $a->getB()->getIterator();
        $this->assertEquals(1, count($a->getB()));
    }
}

/** @ODM\Document */
class GH963A
{
    /** @ODM\Id */
    protected $id;

    /** @ODM\ReferenceMany(targetDocument="GH963B", cascade="all") */
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
class GH963B
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

    public function getValue()
    {
        return $this->value;
    }

}