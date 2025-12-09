<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\PersistentCollection\PersistentCollectionInterface;
use Doctrine\ODM\MongoDB\Tests\BaseTestCase;

use function iterator_to_array;

class GH566Test extends BaseTestCase
{
    public function testFoo(): void
    {
        $class = GH566Document::class;

        $doc1 = new GH566Document();
        $doc2 = new GH566Document();
        $doc3 = new GH566Document();

        $this->dm->persist($doc1);
        $this->dm->persist($doc2);
        $this->dm->persist($doc3);

        $embeddedDoc1           = new GH566EmbeddedDocument();
        $embeddedDoc1->sequence = 1;
        $embeddedDoc1->parent   = $doc1;
        $embeddedDoc2           = new GH566EmbeddedDocument();
        $embeddedDoc2->sequence = 2;
        $embeddedDoc2->parent   = $doc2;

        $doc3->version  = $embeddedDoc2;
        $doc3->versions = new ArrayCollection([
            $embeddedDoc1,
            $embeddedDoc2,
        ]);

        $this->dm->flush();

        self::assertInstanceOf(PersistentCollectionInterface::class, $doc1->children);
        self::assertInstanceOf(PersistentCollectionInterface::class, $doc2->children);
        self::assertInstanceOf(PersistentCollectionInterface::class, $doc3->children);

        /* The inverse-side $children PersistentCollection on these documents
         * is already initialized by this point, so we need to either clear the
         * DocumentManager or reset the PersistentCollections.
         */
        $doc1->children->setInitialized(false);
        $doc2->children->setInitialized(false);
        $doc3->children->setInitialized(false);

        $doc1         = $this->dm->find($class, $doc1->id);
        $doc1Children = iterator_to_array($doc1->children, false);

        self::assertEmpty($doc1Children);

        $doc2         = $this->dm->find($class, $doc2->id);
        $doc2Children = iterator_to_array($doc2->children, false);

        self::assertCount(1, $doc2Children);
        self::assertSame($doc3, $doc2Children[0]);

        $doc3         = $this->dm->find($class, $doc3->id);
        $doc3Children = iterator_to_array($doc3->children, false);

        self::assertEmpty($doc3Children);
    }
}

#[ODM\Document]
class GH566Document
{
    /** @var string|null */
    #[ODM\Id]
    public $id;

    /** @var GH566EmbeddedDocument|null */
    #[ODM\EmbedOne(targetDocument: GH566EmbeddedDocument::class)]
    public $version;

    /** @var Collection<int, GH566EmbeddedDocument> */
    #[ODM\EmbedMany(targetDocument: GH566EmbeddedDocument::class)]
    public $versions;

    /** @var Collection<int, GH566Document> */
    #[ODM\ReferenceMany(targetDocument: self::class, cascade: ['all'], mappedBy: 'version.parent', sort: ['version.sequence' => 'asc'])]
    public $children;

    public function __construct()
    {
        $this->versions = new ArrayCollection();
        $this->children = new ArrayCollection();
    }
}

#[ODM\EmbeddedDocument]
class GH566EmbeddedDocument
{
    /** @var int|null */
    #[ODM\Field(type: 'int')]
    public $sequence = 0;

    /** @var GH566Document|null */
    #[ODM\ReferenceOne(targetDocument: GH566Document::class, cascade: ['all'], inversedBy: 'children')]
    public $parent;
}
