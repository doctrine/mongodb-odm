<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

class GH1011Test extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    public function testClearCollection()
    {
        $doc = new GH1011Document();
        $doc->embeds->add(new GH1011Embedded('test1'));
        $this->dm->persist($doc);
        $this->dm->flush();
        $doc->embeds->clear();
        $doc->embeds->add(new GH1011Embedded('test2'));
        $this->uow->computeChangeSets();
        $this->assertTrue($this->uow->isCollectionScheduledForUpdate($doc->embeds));
        $this->assertFalse($this->uow->isCollectionScheduledForDeletion($doc->embeds));
    }

    public function testReplaceCollection()
    {
        $doc = new GH1011Document();
        $doc->embeds->add(new GH1011Embedded('test1'));
        $this->dm->persist($doc);
        $this->dm->flush();
        $oldCollection = $doc->embeds;
        $doc->embeds = new ArrayCollection();
        $doc->embeds->add(new GH1011Embedded('test2'));
        $this->uow->computeChangeSets();
        $this->assertTrue($this->uow->isCollectionScheduledForUpdate($doc->embeds));
        $this->assertFalse($this->uow->isCollectionScheduledForDeletion($oldCollection));
    }
}

/** @ODM\Document */
class GH1011Document
{
    /** @ODM\Id */
    public $id;

    /** @ODM\EmbedMany(targetDocument="GH1011Embedded", strategy="set") */
    public $embeds;

    public function __construct()
    {
        $this->embeds = new ArrayCollection();
    }
}

/** @ODM\EmbeddedDocument */
class GH1011Embedded
{
    /** @ODM\Field(type="string") */
    public $name;

    public function __construct($name)
    {
        $this->name = $name;
    }
}
