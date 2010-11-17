<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

class MODM56Test extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    public function testTest()
    {
        $parent = new MODM56Parent('Parent');
        $this->dm->persist($parent);
        $this->dm->flush();

        $childOne = new MODM56Child('Child One');
        $parent->children[] = $childOne;

        $childTwo = new MODM56Child('Child Two');
        $parent->children[] = $childTwo;
        $this->dm->flush(array('safe' => true));

        $test = $this->dm->getDocumentCollection(__NAMESPACE__.'\MODM56Parent')->findOne();

        $this->assertEquals('Parent', $test['name']);
        $this->assertInstanceOf('\MongoDate', $test['updatedAt']);
        $this->assertEquals(2, count($test['children']));
        $this->assertEquals('Child One', $test['children'][0]['name']);
        $this->assertEquals('Child Two', $test['children'][1]['name']);
    }
}

/** @Document @HasLifecycleCallbacks */
class MODM56Parent
{
    /** @Id */
    public $id;

    /** @String */
    public $name;

    /** @Date */
    public $updatedAt;

    /** @EmbedMany(targetDocument="MODM56Child") */
    public $children = array();

    public function __construct($name)
    {
        $this->name = $name;
    }

    /** @PreUpdate */
    public function preUpdate()
    {
        $this->updatedAt = new \DateTime();
    }
}

/** @EmbeddedDocument */
class MODM56Child
{
    /** @Id */
    public $id;

    /** @String */
    public $name;

    public function __construct($name)
    {
        $this->name = $name;
    }
}