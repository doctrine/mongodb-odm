<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

class MODM76Test extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    public function testTest()
    {
        $c1 = new MODM76C;
        $c2 = new MODM76C;

        $b = new MODM76B($c1);
        $a = new MODM76A(array($b), array($c1));

        $this->dm->persist($a);
        $this->dm->persist($b);
        $this->dm->persist($c1);
        $this->dm->persist($c2);
        $this->dm->flush();

        $this->assertTrue($a->getId() != null);
    }
}

/** @ODM\Document */
class MODM76A
{
    /** @ODM\Id */
    protected $id;

    /** @ODM\Field(type="string") */
    protected $test = 'test';

    /** @ODM\EmbedMany(targetDocument="MODM76B") */
    protected $b = array();

    /** @ODM\ReferenceMany(targetDocument="MODM76C") */
    protected $c = array();

    public function __construct($b, $c)
    {
        $this->b = new ArrayCollection($b);
        $this->c = new ArrayCollection($c);
    }

    public function getB()
    {
        return $this->b;
    }

    public function getC()
    {
        return $this->c;
    }

    public function getId()
    {
        return $this->id;
    }
}

/** @ODM\EmbeddedDocument */
class MODM76B
{
    /** @ODM\ReferenceOne(targetDocument="MODM76C") */
    protected $c;

    public function __construct($c)
    {
        $this->c = $c;
    }

    public function getC()
    {
        return $this->c;
    }
}

/** @ODM\Document */
class MODM76C
{
    /** @ODM\Id */
    protected $id;
}
