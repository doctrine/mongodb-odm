<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

require_once __DIR__ . '/../../../../../../TestInit.php';

class MODM52Test extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    public function testTest()
    {
        $emb = new MODM52Embedded(array(new MODM52Embedded(), new MODM52Embedded()));
        $doc = new MODM52Doc(array($emb));

        $this->dm->persist($doc);
        $this->dm->flush(array('safe' => true));
        $this->dm->refresh($doc);

        // change nested embedded collection:
        $doc->getItem(0)->removeItem(1);
        $before = count($doc->getItem(0)->getItems());

        $this->dm->persist($doc);
        $this->dm->flush();
        $this->dm->refresh($doc);

        $after = count($doc->getItem(0)->getItems());
        $this->assertEquals(1, $before);
        $this->assertEquals(1, $after);
    }
}

/**
 * @MappedSuperClass
 */
class MODM52Container
{
    /** @String */
    protected $tmp = 'ensureSaved';

    /** @EmbedMany(targetDocument="MODM52Embedded", strategy="set") */
    protected $items = array();

    function __construct($items = null) {if($items) $this->items = $items;}
    function getItems() {return $this->items;}
    function getItem($index) {return $this->items[$index];}
    function removeItem($i) {unset($this->items[$i]);}
}

/** @EmbeddedDocument */
class MODM52Embedded extends MODM52Container
{}

/** @Document(db="tests", collection="tests") */
class MODM52Doc extends MODM52Container
{
    /** @Id */
    protected $id;
}