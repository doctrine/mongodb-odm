<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

class MODM29Test extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    public function testTest()
    {
        $collection = new \Doctrine\Common\Collections\ArrayCollection(array(
            new MODM29Embedded('0'),
            new MODM29Embedded('1'),
            new MODM29Embedded('2')
        ));

        // TEST CASE:
        $doc = new MODM29Doc($collection);

        $this->dm->persist($doc);
        $this->dm->flush();

        // place element '0' after '1'
        $collection = new \Doctrine\Common\Collections\ArrayCollection(array(
            $collection[1],
            $collection[0],
            $collection[2]
        ));

        $doc->set($collection);

        // changing value together with reordering causes issue when saving:
        $collection[1]->set('tmp');

        $this->dm->persist($doc);
        $this->dm->flush();

        $this->dm->refresh($doc);

        $array = array();
        foreach($doc->get() as $value) {
            $array[] = $value->get();
        }
        $this->assertEquals(array('1', 'tmp', '2'), $array);
    }
}

/** @ODM\Document */
class MODM29Doc
{
    /** @ODM\Id */
    protected $id;

    /** @ODM\EmbedMany(targetDocument="MODM29Embedded", strategy="set") */
    protected $collection;

    function __construct($c) {$this->set($c);}

    function set($c) {$this->collection = $c;}
    function get() {return $this->collection;}
}

/** @ODM\EmbeddedDocument */
class MODM29Embedded
{
    /** @ODM\Field(type="string") */
    protected $val;

    function __construct($val) {$this->set($val);}
    function get() {return $this->val;}
    function set($val) {$this->val = $val;}
}
