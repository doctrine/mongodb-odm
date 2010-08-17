<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

require_once __DIR__ . '/../../../../../../TestInit.php';

class MODM45Test extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    public function testTest()
    {
        $a = new a();
        $a->setB(new b());

        $this->dm->persist($a);
        $this->dm->flush();
        $this->dm->clear();

        $a = $this->dm->loadByID(__NAMESPACE__.'\a', $a->getId());
        $c = (null !== $a->getB()); 
        $this->assertTrue($c); // returns false, while expecting true
    }
}

/** @Document(collection="modm45_test") */
class a
{
    /** @Id */
    protected $id;

    /** @String */
    protected $tmp = 'WorkaroundToBeSaved';

    /** @EmbedOne(targetDocument="b", cascade="all") */
    protected $b;

    function getId()  {return $this->id;}
    function getB()   {return $this->b;}
    function setB($b) {$this->b = $b;}
}

/** @EmbeddedDocument */
class b
{
    /** @String */
    protected $val;
    function setVal($val) {$this->val = $val;}
    function getVal() {return $this->val;}
}