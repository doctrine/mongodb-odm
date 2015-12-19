<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

class GH1117Test extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    public function testAddOnUninitializedCollection()
    {
        $doc = new GH1117Document();
        $doc->embeds->add(new GH1117EmbeddedDocument('one'));
        $this->dm->persist($doc);
        $this->dm->flush();
        $this->dm->clear();

        $doc = $this->dm->getRepository(get_class($doc))->find($doc->id);
        $doc->embeds->add(new GH1117EmbeddedDocument('two'));
        $this->dm->flush();
        $this->dm->clear();

        $doc = $this->dm->getRepository(get_class($doc))->find($doc->id);
        $this->assertCount(2, $doc->embeds);
        $this->assertEquals('one', $doc->embeds[0]->value);
        $this->assertEquals('two', $doc->embeds[1]->value);
    }
}

/**
 * @ODM\Document
 */
class GH1117Document
{
    /** @ODM\Id */
    public $id;

    /** @ODM\EmbedMany(strategy="set", targetDocument="GH1117EmbeddedDocument") */
    public $embeds;

    public function __construct()
    {
        $this->embeds = new ArrayCollection();
    }
}

/**
 * @ODM\EmbeddedDocument
 */
class GH1117EmbeddedDocument
{
    /** @ODM\Field(type="string") */
    public $value;

    public function __construct($value)
    {
        $this->value = $value;
    }
}
