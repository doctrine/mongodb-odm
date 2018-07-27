<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Tests\BaseTest;
use MongoDB\BSON\UTCDateTime;

class MODM56Test extends BaseTest
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
        $this->dm->flush();

        $test = $this->dm->getDocumentCollection(MODM56Parent::class)->findOne();

        $this->assertEquals('Parent', $test['name']);
        $this->assertInstanceOf(UTCDateTime::class, $test['updatedAt']);
        $this->assertCount(2, $test['children']);
        $this->assertEquals('Child One', $test['children'][0]['name']);
        $this->assertEquals('Child Two', $test['children'][1]['name']);
    }
}

/** @ODM\Document @ODM\HasLifecycleCallbacks */
class MODM56Parent
{
    /** @ODM\Id */
    public $id;

    /** @ODM\Field(type="string") */
    public $name;

    /** @ODM\Field(type="date") */
    public $updatedAt;

    /** @ODM\EmbedMany(targetDocument=MODM56Child::class) */
    public $children = [];

    public function __construct($name)
    {
        $this->name = $name;
    }

    /** @ODM\PreUpdate */
    public function preUpdate()
    {
        $this->updatedAt = new \DateTime();
    }
}

/** @ODM\EmbeddedDocument */
class MODM56Child
{
    /** @ODM\Id */
    public $id;

    /** @ODM\Field(type="string") */
    public $name;

    public function __construct($name)
    {
        $this->name = $name;
    }
}
