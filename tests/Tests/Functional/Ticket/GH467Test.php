<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\PersistentCollection;
use Doctrine\ODM\MongoDB\Tests\BaseTestCase;

class GH467Test extends BaseTestCase
{
    public function testMergeDocumentWithUnsetCollectionFields(): void
    {
        $doc = new GH467Document();

        $this->dm->persist($doc);
        $this->dm->flush();
        $this->dm->clear();

        $doc = $this->dm->merge($doc);

        self::assertNull($doc->col, 'Unset basic collections are not initialized');
        self::assertInstanceOf(PersistentCollection::class, $doc->embedMany, 'Unset EmbedMany collections are initialized as empty PersistentCollections');
        self::assertEmpty($doc->embedMany, 'Unset EmbedMany collections are initialized as empty PersistentCollections');
        self::assertInstanceOf(PersistentCollection::class, $doc->refMany, 'Unset ReferenceMany collections are initialized as empty PersistentCollections');
        self::assertEmpty($doc->refMany, 'Unset ReferenceMany collections are initialized as empty PersistentCollections');
    }
}

#[ODM\Document]
class GH467Document
{
    /** @var string|null */
    #[ODM\Id]
    public $id;

    /** @var mixed[] */
    #[ODM\Field(type: 'collection')]
    public $col;

    /** @var Collection<int, GH467EmbeddedDocument> */
    #[ODM\EmbedMany(targetDocument: GH467EmbeddedDocument::class)]
    public $embedMany;

    /** @var Collection<int, GH467EmbeddedDocument> */
    #[ODM\ReferenceMany(targetDocument: GH467EmbeddedDocument::class)]
    public $refMany;
}

#[ODM\EmbeddedDocument]
class GH467EmbeddedDocument
{
    /** @var string|null */
    #[ODM\Id]
    public $id;
}

#[ODM\Document]
class GH467ReferencedDocument
{
    /** @var string|null */
    #[ODM\Id]
    public $id;
}
