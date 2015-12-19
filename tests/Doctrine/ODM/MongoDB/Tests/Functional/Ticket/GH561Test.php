<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

class GH561Test extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    public function testPersistMainDocument()
    {
        $embeddedDocument = new GH561EmbeddedDocument();
        $embeddedDocument->embeddedDocuments->add(new GH561AnotherEmbeddedDocument('foo'));

        $document = new GH561Document();
        $document->embeddedDocuments->add($embeddedDocument);

        $this->dm->persist($document);
        $this->dm->flush();
        $this->dm->clear();

        $document = $this->dm->find(__NAMESPACE__.'\GH561Document', $document->id);
        $this->assertInstanceOf(__NAMESPACE__.'\GH561Document', $document);
        $this->assertCount(1, $document->embeddedDocuments);

        $embeddedDocument = $document->embeddedDocuments->first();
        $this->assertInstanceOf(__NAMESPACE__.'\GH561EmbeddedDocument', $embeddedDocument);
        $this->assertCount(1, $embeddedDocument->embeddedDocuments);

        $anotherEmbeddedDocument = $embeddedDocument->embeddedDocuments->first();
        $this->assertInstanceOf(__NAMESPACE__.'\GH561AnotherEmbeddedDocument', $anotherEmbeddedDocument);
        $this->assertEquals('foo', $anotherEmbeddedDocument->name);
    }
}

/** @ODM\Document */
class GH561Document
{
    /** @ODM\Id */
    public $id;

    /** @ODM\EmbedMany(targetDocument="GH561EmbeddedDocument", strategy="set") */
    public $embeddedDocuments;

    public function __construct()
    {
        $this->embeddedDocuments = new ArrayCollection();
    }
}

/** @ODM\EmbeddedDocument */
class GH561EmbeddedDocument
{
    /** @ODM\EmbedMany(targetDocument="GH561AnotherEmbeddedDocument", strategy="set") */
    public $embeddedDocuments;

    public function __construct()
    {
        $this->embeddedDocuments = new ArrayCollection();
    }
}

/** @ODM\EmbeddedDocument */
class GH561AnotherEmbeddedDocument
{
    /** @ODM\Field(type="string") */
    public $name;

    public function __construct($name)
    {
        $this->name = $name;
    }
}
