<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

class GH467Test extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    public function testMergeDocumentWithUnsetCollectionFields()
    {
        $doc = new GH467Document();

        $this->dm->persist($doc);
        $this->dm->flush();
        $this->dm->clear();

        $doc = $this->dm->merge($doc);

        $this->assertNull($doc->col, 'Unset basic collections are not initialized');
        $this->assertInstanceOf('Doctrine\ODM\MongoDB\PersistentCollection', $doc->embedMany, 'Unset EmbedMany collections are initialized as empty PersistentCollections');
        $this->assertCount(0, $doc->embedMany, 'Unset EmbedMany collections are initialized as empty PersistentCollections');
        $this->assertInstanceOf('Doctrine\ODM\MongoDB\PersistentCollection', $doc->refMany, 'Unset ReferenceMany collections are initialized as empty PersistentCollections');
        $this->assertCount(0, $doc->refMany, 'Unset ReferenceMany collections are initialized as empty PersistentCollections');
    }
}

/** @ODM\Document */
class GH467Document
{
    /** @ODM\Id */
    public $id;

    /** @ODM\Field(type="collection") */
    public $col;

    /** @ODM\EmbedMany(targetDocument="GH467EmbeddedDocument") */
    public $embedMany;

    /** @ODM\ReferenceMany(targetDocument="GH467EmbeddedDocument") */
    public $refMany;
}

/** @ODM\EmbeddedDocument */
class GH467EmbeddedDocument
{
    /** @ODM\Id */
    public $id;
}

/** @ODM\Document */
class GH467ReferencedDocument
{
    /** @ODM\Id */
    public $id;
}
