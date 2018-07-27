<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\PersistentCollection;
use Doctrine\ODM\MongoDB\Tests\BaseTest;

class GH467Test extends BaseTest
{
    public function testMergeDocumentWithUnsetCollectionFields()
    {
        $doc = new GH467Document();

        $this->dm->persist($doc);
        $this->dm->flush();
        $this->dm->clear();

        $doc = $this->dm->merge($doc);

        $this->assertNull($doc->col, 'Unset basic collections are not initialized');
        $this->assertInstanceOf(PersistentCollection::class, $doc->embedMany, 'Unset EmbedMany collections are initialized as empty PersistentCollections');
        $this->assertCount(0, $doc->embedMany, 'Unset EmbedMany collections are initialized as empty PersistentCollections');
        $this->assertInstanceOf(PersistentCollection::class, $doc->refMany, 'Unset ReferenceMany collections are initialized as empty PersistentCollections');
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

    /** @ODM\EmbedMany(targetDocument=GH467EmbeddedDocument::class) */
    public $embedMany;

    /** @ODM\ReferenceMany(targetDocument=GH467EmbeddedDocument::class) */
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
