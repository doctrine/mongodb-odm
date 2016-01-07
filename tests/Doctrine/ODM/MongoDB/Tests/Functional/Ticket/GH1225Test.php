<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

class GH1225Test extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    public function testRemoveAddEmbeddedDocToExistingDocumentWithPreUpdateHook()
    {
        $doc = new GH1225Document();
        $doc->embeds->add(new GH1225EmbeddedDocument('foo'));
        $this->dm->persist($doc);
        $this->dm->flush();
        $this->dm->clear();

        $doc = $this->dm->getRepository(get_class($doc))->find($doc->id);
        $embeddedDoc = $doc->embeds->first();
        $doc->embeds->clear();
        $doc->embeds->add($embeddedDoc);
        $this->dm->flush();
        $this->dm->clear();

        $doc = $this->dm->getRepository(get_class($doc))->find($doc->id);
        $this->assertCount(1, $doc->embeds);
    }
}

/**
 * @ODM\Document
 * @ODM\HasLifecycleCallbacks
 */
class GH1225Document
{
    /** @ODM\Id */
    public $id;

    /** @ODM\EmbedMany(strategy="atomicSet", targetDocument="GH1225EmbeddedDocument") */
    public $embeds;

    public function __construct()
    {
        $this->embeds = new ArrayCollection();
    }

    /**
     * @ODM\PreUpdate
     */
    public function exampleHook()
    {
    }
}

/**
 * @ODM\EmbeddedDocument
 */
class GH1225EmbeddedDocument
{
    /** @ODM\Field(type="string") */
    public $value;

    public function __construct($value)
    {
        $this->value = $value;
    }
}
