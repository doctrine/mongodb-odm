<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

class MODM52Test extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    public function testTest()
    {
        $emb = new MODM52Embedded(array(new MODM52Embedded(null, 'c1'), new MODM52Embedded(null, 'c2')), 'b');
        $doc = new MODM52Doc(array($emb), 'a');

        $this->dm->persist($doc);
        $this->dm->flush();

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
 * @ODM\MappedSuperClass
 */
class MODM52Container
{
    /** @ODM\Field(type="string") */
    public $value;

    /** @ODM\EmbedMany(targetDocument="MODM52Embedded", strategy="set") */
    public $items = array();

    public function __construct($items = null, $value = null)
    {
        if ($items) {
            $this->items = $items;
        }
        $this->value = $value;
    }

    public function getItems()
    {
        return $this->items;
    }

    public function getItem($index)
    {
        return $this->items[$index];
    }

    public function removeItem($i)
    {
        unset($this->items[$i]);
    }
}

/** @ODM\EmbeddedDocument */
class MODM52Embedded extends MODM52Container
{}

/** @ODM\Document */
class MODM52Doc extends MODM52Container
{
    /** @ODM\Id */
    protected $id;
}
