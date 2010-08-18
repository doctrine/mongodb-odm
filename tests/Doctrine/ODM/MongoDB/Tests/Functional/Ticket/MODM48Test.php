<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

require_once __DIR__ . '/../../../../../../TestInit.php';

class MODM48Test extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    public function testTest()
    {
        $a = new MODM48A();
        $a->b = new MODM48B();
        $this->dm->persist($a);
        $this->dm->flush();
        $this->dm->clear();

        $a = $this->dm->findOne(__NAMESPACE__.'\MODM48A');
        $this->assertNotNull($a);

        $a->getB()->setVal('test');

        $this->dm->flush(array('safe' => true));
        $this->dm->clear();

        $a = $this->dm->findOne(__NAMESPACE__.'\MODM48A');
        $this->assertEquals('test', $a->getB()->getVal());
    }
}

/** @Document(db="modm48_tests", collection="a") */
class MODM48A
{
    /** @Id */
    public $id;

    /** @EmbedOne(targetDocument="MODM48B", cascade="all") */
    public $b;

    function getId()  {return $this->id;}
    function getB()   {return $this->b;}
    function setB($b) {$this->b = $b;}
}

/** @EmbeddedDocument */
class MODM48B
{
    /** @String */
    public $val;

    function setVal($val) {$this->val = $val;}
    function getVal() {return $this->val;}
}