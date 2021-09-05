<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\PersistentCollection;
use Doctrine\ODM\MongoDB\Tests\BaseTest;

class GH467Test extends BaseTest
{
    public function testMergeDocumentWithUnsetCollectionFields(): void
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
    /**
     * @ODM\Id
     *
     * @var string|null
     */
    public $id;

    /**
     * @ODM\Field(type="collection")
     *
     * @var mixed[]
     */
    public $col;

    /**
     * @ODM\EmbedMany(targetDocument=GH467EmbeddedDocument::class)
     *
     * @var Collection<int, GH467EmbeddedDocument>
     */
    public $embedMany;

    /**
     * @ODM\ReferenceMany(targetDocument=GH467EmbeddedDocument::class)
     *
     * @var Collection<int, GH467EmbeddedDocument>
     */
    public $refMany;
}

/** @ODM\EmbeddedDocument */
class GH467EmbeddedDocument
{
    /**
     * @ODM\Id
     *
     * @var string|null
     */
    public $id;
}

/** @ODM\Document */
class GH467ReferencedDocument
{
    /**
     * @ODM\Id
     *
     * @var string|null
     */
    public $id;
}
