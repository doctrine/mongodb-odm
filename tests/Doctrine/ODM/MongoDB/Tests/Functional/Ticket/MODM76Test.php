<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\Common\Collections\ArrayCollection;

class MODM76Test extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    public function testTest()
    {
        $c1 = new MODM76C;
        $c2 = new MODM76C;

        $b = new MODM76B($c1);
        $a = new MODM76A(array($b), array($c1));

        $this->dm->persist($a);
        $this->dm->flush();

        $this->assertTrue($a->getId() != null);
    }
}

/** @Document(db="tests", collection="tests") */
class MODM76A
{
    /** @Id */
    protected $id;

    /** @String */
    protected $test = 'test';

    /** @EmbedMany(targetDocument="MODM76B") */
    protected $b = array();

    /** @ReferenceMany(targetDocument="MODM76C") */
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

/** @EmbeddedDocument */
class MODM76B
{
    /** @ReferenceOne(targetDocument="MODM76C") */
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

/** @Document(db="tests", collection="tests2") */
class MODM76C
{
    /** @Id */
    protected $id;
}