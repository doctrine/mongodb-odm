<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 *
 * @group GH561
 */
class GH561Test extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    public function testPersistMainDocument()
    {
        $embeddedDocument = new GH561TestEmbeddedDocument('test');

        $anotherEmbeddedDocuments = array(
            new GH561AnotherEmbedded('doc1'),
            new GH561AnotherEmbedded('doc2'),
            new GH561AnotherEmbedded('doc3')
        );
        $embeddedDocument->setEmbeddedDocuments($anotherEmbeddedDocuments);

        $embeddedDocuments = array($embeddedDocument);
        $testDoc = new GH561TestDocument();
        $testDoc->setEmbeddedDocuments($embeddedDocuments);

        $this->dm->persist($testDoc);
        $this->dm->flush();

        $testDoc = $this->dm->find(__NAMESPACE__.'\GH561TestDocument', $testDoc->id);
        $this->assertEquals($embeddedDocuments, $testDoc->embeddedDocuments->toArray());

        $embeddedDocument = $testDoc->embeddedDocuments->first();
        $this->assertTrue($embeddedDocument instanceof GH561TestEmbeddedDocument);
        $this->assertEquals($anotherEmbeddedDocuments, $embeddedDocument->otherEmbedded->toArray());

        $anotherEmbeddedDocument = $embeddedDocument->otherEmbedded->first();
        $this->assertTrue($anotherEmbeddedDocument instanceof GH561AnotherEmbedded);
        $this->assertEquals('doc1', $anotherEmbeddedDocument->something);
    }
}

/** @ODM\Document */
class GH561TestDocument
{
    /** @ODM\Id */
    public $id;

    // Note: Test case fails with default "pushAll" strategy, but "set" works
    /** @ODM\EmbedMany(targetDocument="GH561TestEmbeddedDocument", strategy="set") */
    public $embeddedDocuments;

    public function __construct() {
        $this->embeddedDocuments = new ArrayCollection();
    }

    /**
     * Sets children
     *
     * If $images is not an array or Traversable object, this method will simply
     * clear the images collection property.  If any elements in the parameter
     * are not an Image object, this method will attempt to convert them to one
     * by mapping array indexes (size URL's are required, cropMetadata is not).
     * Any invalid elements will be ignored.
     *
     * @param array|Traversable $children
     */
    public function setEmbeddedDocuments($embeddedDocuments) {
        $this->embeddedDocuments->clear();

        if (! (is_array($embeddedDocuments) || $embeddedDocuments instanceof \Traversable)) {
            return;
        }

        foreach ($embeddedDocuments as $embeddedDocument) {
            $this->embeddedDocuments->add($embeddedDocument);
        }
    }
}

/** @ODM\EmbeddedDocument */
class GH561TestEmbeddedDocument
{
    public function __construct()
    {
        $this->otherEmbedded = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * @@ODM\EmbedMany(targetDocument="AnotherEmbedded", strategy="set")
     */
    public $otherEmbedded;

    public function setEmbeddedDocuments($embeddedDocuments) {
        $this->otherEmbedded->clear();

        if (! (is_array($embeddedDocuments) || $embeddedDocuments instanceof \Traversable)) {
            return;
        }

        foreach ($embeddedDocuments as $embeddedDocument) {
            $this->otherEmbedded->add($embeddedDocument);
        }
    }
}

/**
 * @@ODM\EmbeddedDocument
 */
class GH561AnotherEmbedded
{
    public function __construct($string)
    {
        $this->something = $string;
    }

    /**
     * @@ODM\String
     */
    public $something;
}