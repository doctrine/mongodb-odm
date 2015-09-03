<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

class GHXXXXTest extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    public function testAddOnUninitializedCollection()
    {
        $doc = new GHXXXXDocument();
        $mySubDoc = new GHXXXXEmbeddedDocument('foo');
        $doc->embeds->add($mySubDoc);
        $this->dm->persist($doc);
        $this->dm->flush();
        $this->dm->clear();

        $doc = $this->dm->getRepository(get_class($doc))->find($doc->id);
        $removedEmbeddedDocumentOne = $doc->embeds->first();
        $doc->embeds->clear();
        $doc->embeds->add($removedEmbeddedDocumentOne);
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
class GHXXXXDocument
{
    /** @ODM\Id */
    public $id;

    /** @ODM\EmbedMany(strategy="atomicSet", targetDocument="GHXXXXEmbeddedDocument") */
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
class GHXXXXEmbeddedDocument
{
    /** @ODM\String */
    public $value;

    public function __construct($value)
    {
        $this->value = $value;
    }
}
