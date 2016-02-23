<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

class MODM92Test extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    public function testDocumentWithEmbeddedDocuments()
    {
        $embeddedDocuments = array(new MODM92TestEmbeddedDocument('foo'));

        $testDoc = new MODM92TestDocument();
        $testDoc->setEmbeddedDocuments($embeddedDocuments);
        $this->dm->persist($testDoc);
        $this->dm->flush();
        $this->dm->clear();

        $testDoc = $this->dm->find(__NAMESPACE__.'\MODM92TestDocument', $testDoc->id);
        $this->assertEquals($embeddedDocuments, $testDoc->embeddedDocuments->toArray());

        $embeddedDocuments = array(new MODM92TestEmbeddedDocument('bar'));

        $testDoc->setEmbeddedDocuments($embeddedDocuments);
        $this->assertEquals($embeddedDocuments, $testDoc->embeddedDocuments->toArray());

        $this->dm->flush();
        $this->dm->clear();
        $testDoc = $this->dm->find(__NAMESPACE__.'\MODM92TestDocument', $testDoc->id);

        $this->assertEquals($embeddedDocuments, $testDoc->embeddedDocuments->toArray());
    }
}

/** @ODM\Document */
class MODM92TestDocument
{
    /** @ODM\Id */
    public $id;

    // Note: Test case fails with default "pushAll" strategy, but "set" works
    /** @ODM\EmbedMany(targetDocument="MODM92TestEmbeddedDocument") */
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
class MODM92TestEmbeddedDocument
{
    /** @ODM\Field(type="string") */
    public $name;

    public function __construct($name) {
        $this->name = $name;
    }
}
