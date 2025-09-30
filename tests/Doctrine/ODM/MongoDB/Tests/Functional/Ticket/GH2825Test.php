<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\Tests\BaseTestCase;
use MongoDB\BSON\ObjectId;

class GH2825Test extends BaseTestCase
{
    public function testQueryBuilderUpdatesEmbedOneCorrectly(): void
    {
        $document           = new GH2825Document('foo');
        $document->embedded = new GH2825Embedded('level 1');

        $this->dm->persist($document);
        $this->dm->flush();

        $embedded = new GH2825Embedded('level 2');

        $this->dm->persist($embedded);

        $this->dm->createQueryBuilder(GH2825Document::class)
            ->updateOne()
            ->field('id')->equals($document->id)
            ->field('embedded.embedded')->set($embedded)
            ->getQuery()
            ->execute();

        $result = $this->dm->getDocumentCollection(GH2825Document::class)
            ->findOne(['_id' => new ObjectId($document->id)]);

        self::assertSame('level 1', $result['embedded']['renamed']);
        self::assertSame('level 2', $result['embedded']['embedded']['renamed']);
    }

    public function testQueryBuilderUpdatesEmbedManyCorrectly(): void
    {
        $document                      = new GH2825Document('foo');
        $document->embeddedDocuments[] = new GH2825Embedded('level 1');

        $this->dm->persist($document);
        $this->dm->flush();

        $embedded = new GH2825Embedded('level 2');

        $this->dm->persist($embedded);

        $this->dm->createQueryBuilder(GH2825Document::class)
            ->updateOne()
            ->field('id')->equals($document->id)
            ->field('embeddedDocuments.property')->equals('level 1')
            ->field('embeddedDocuments.$.embedded')->set($embedded)
            ->getQuery()
            ->execute();

        $result = $this->dm->getDocumentCollection(GH2825Document::class)
            ->findOne(['_id' => new ObjectId($document->id)]);

        self::assertIsArray($result['embeddedDocuments']);
        self::assertSame('level 1', $result['embeddedDocuments'][0]['renamed']);
        self::assertSame('level 2', $result['embeddedDocuments'][0]['embedded']['renamed']);
    }

    public function testQueryBuilderReplacesEmbedManyCorrectly(): void
    {
        $document                      = new GH2825Document('foo');
        $document->embeddedDocuments[] = new GH2825Embedded('original');

        $this->dm->persist($document);
        $this->dm->flush();

        $embedded = new GH2825Embedded('replaced');

        $this->dm->persist($embedded);

        $this->dm->createQueryBuilder(GH2825Document::class)
            ->updateOne()
            ->field('id')->equals($document->id)
            ->field('embeddedDocuments.property')->equals('original')
            ->field('embeddedDocuments.$')->set($embedded)
            ->getQuery()
            ->execute();

        $result = $this->dm->getDocumentCollection(GH2825Document::class)
            ->findOne(['_id' => new ObjectId($document->id)]);

        self::assertIsArray($result['embeddedDocuments']);
        self::assertSame('replaced', $result['embeddedDocuments'][0]['renamed']);
    }

    public function testQueryBuilderUpdatesReferenceOneCorrectly(): void
    {
        $document           = new GH2825Document('document');
        $document->embedded = new GH2825Embedded('embedded');
        $reference          = new GH2825Document('referenced');

        $this->dm->persist($document);
        $this->dm->persist($reference);

        $this->dm->flush();

        $this->dm->createQueryBuilder(GH2825Document::class)
            ->updateOne()
            ->field('id')->equals($document->id)
            ->field('referenceStoreAsId')->set($reference)
            ->field('referenceStoreAsRef')->set($reference)
            ->field('referenceStoreAsDbRef')->set($reference)
            ->field('embedded.referenceStoreAsId')->set($reference)
            ->field('embedded.referenceStoreAsRef')->set($reference)
            ->field('embedded.referenceStoreAsDbRef')->set($reference)
            ->getQuery()
            ->execute();

        $result = $this->dm->getDocumentCollection(GH2825Document::class)
            ->findOne(['_id' => new ObjectId($document->id)], ['typeMap' => ['root' => 'array', 'document' => 'array']]);

        $referenceId = new ObjectId($reference->id);

        self::assertEquals($referenceId, $result['referenceStoreAsId']);
        self::assertEquals(['id' => $referenceId], $result['referenceStoreAsRef']);
        self::assertEquals(['$ref' => 'GH2825Document', '$id' => $referenceId], $result['referenceStoreAsDbRef']);

        self::assertEquals($referenceId, $result['embedded']['referenceStoreAsId']);
        self::assertEquals(['id' => $referenceId], $result['embedded']['referenceStoreAsRef']);
        self::assertEquals(['$ref' => 'GH2825Document', '$id' => $referenceId], $result['embedded']['referenceStoreAsDbRef']);
    }

    public function testQueryBuilderUpdatesReferenceManyCorrectly(): void
    {
        $document                      = new GH2825Document('document');
        $document->embeddedDocuments[] = new GH2825Embedded('embedded');
        $reference                     = new GH2825Document('referenced');

        $this->dm->persist($document);
        $this->dm->persist($reference);

        $this->dm->flush();

        $this->dm->createQueryBuilder(GH2825Document::class)
            ->updateOne()
            ->field('id')->equals($document->id)
            ->field('embeddedDocuments.property')->equals('embedded')
            ->field('embeddedDocuments.$.referenceStoreAsId')->set($reference)
            ->field('embeddedDocuments.$.referenceStoreAsRef')->set($reference)
            ->field('embeddedDocuments.$.referenceStoreAsDbRef')->set($reference)
            ->getQuery()
            ->execute();

        $result = $this->dm->getDocumentCollection(GH2825Document::class)
            ->findOne(['_id' => new ObjectId($document->id)], ['typeMap' => ['root' => 'array', 'document' => 'array']]);

        $referenceId = new ObjectId($reference->id);

        self::assertIsArray($result['embeddedDocuments']);

        self::assertEquals($referenceId, $result['embeddedDocuments'][0]['referenceStoreAsId']);
        self::assertEquals(['id' => $referenceId], $result['embeddedDocuments'][0]['referenceStoreAsRef']);
        self::assertEquals(['$ref' => 'GH2825Document', '$id' => $referenceId], $result['embeddedDocuments'][0]['referenceStoreAsDbRef']);
    }

    public function testQueryBuilderReplacesReferenceManyCorrectly(): void
    {
        $document                                    = new GH2825Document('document');
        $reference                                   = new GH2825Document('original');
        $otherReference                              = new GH2825Document('original');
        $document->referencedDocumentsStoreAsId[]    = $reference;
        $document->referencedDocumentsStoreAsRef[]   = $reference;
        $document->referencedDocumentsStoreAsDbRef[] = $reference;

        $this->dm->persist($document);
        $this->dm->persist($reference);
        $this->dm->persist($otherReference);

        $this->dm->flush();

        $this->dm->createQueryBuilder(GH2825Document::class)
            ->updateOne()
            ->field('id')->equals($document->id)
            ->field('referencedDocumentsStoreAsId.0')->set($otherReference)
            ->field('referencedDocumentsStoreAsRef.0')->set($otherReference)
            ->field('referencedDocumentsStoreAsDbRef.0')->set($otherReference)
            ->getQuery()
            ->execute();

        $result = $this->dm->getDocumentCollection(GH2825Document::class)
            ->findOne(['_id' => new ObjectId($document->id)], ['typeMap' => ['root' => 'array', 'document' => 'array']]);

        $referenceId = new ObjectId($otherReference->id);

        self::assertIsArray($result['referencedDocumentsStoreAsId']);
        self::assertIsArray($result['referencedDocumentsStoreAsRef']);
        self::assertIsArray($result['referencedDocumentsStoreAsDbRef']);

        self::assertEquals($referenceId, $result['referencedDocumentsStoreAsId'][0]);
        self::assertEquals(['id' => $referenceId], $result['referencedDocumentsStoreAsRef'][0]);
        self::assertEquals(['$ref' => 'GH2825Document', '$id' => $referenceId], $result['referencedDocumentsStoreAsDbRef'][0]);
    }
}

#[ODM\Document]
class GH2825Document
{
    #[ODM\Id]
    public string|null $id;

    #[ODM\Field]
    public string $name;

    #[ODM\EmbedOne(targetDocument: GH2825Embedded::class)]
    public GH2825Embedded|null $embedded = null;

    #[ODM\ReferenceOne(targetDocument: self::class, storeAs: ClassMetadata::REFERENCE_STORE_AS_ID)]
    public GH2825Document|null $referenceStoreAsId = null;

    #[ODM\ReferenceOne(targetDocument: self::class, storeAs: ClassMetadata::REFERENCE_STORE_AS_REF)]
    public GH2825Document|null $referenceStoreAsRef = null;

    #[ODM\ReferenceOne(targetDocument: self::class, storeAs: ClassMetadata::REFERENCE_STORE_AS_DB_REF)]
    public GH2825Document|null $referenceStoreAsDbRef = null;

    /** @var Collection<int, GH2825Embedded> */
    #[ODM\EmbedMany(targetDocument: GH2825Embedded::class)]
    public Collection $embeddedDocuments;

    /** @var Collection<int, GH2825Document> */
    #[ODM\ReferenceMany(targetDocument: self::class, storeAs: ClassMetadata::REFERENCE_STORE_AS_ID)]
    public Collection $referencedDocumentsStoreAsId;

    /** @var Collection<int, GH2825Document> */
    #[ODM\ReferenceMany(targetDocument: self::class, storeAs: ClassMetadata::REFERENCE_STORE_AS_REF)]
    public Collection $referencedDocumentsStoreAsRef;

    /** @var Collection<int, GH2825Document> */
    #[ODM\ReferenceMany(targetDocument: self::class, storeAs: ClassMetadata::REFERENCE_STORE_AS_DB_REF)]
    public Collection $referencedDocumentsStoreAsDbRef;

    public function __construct(string $name)
    {
        $this->name                            = $name;
        $this->embeddedDocuments               = new ArrayCollection();
        $this->referencedDocumentsStoreAsId    = new ArrayCollection();
        $this->referencedDocumentsStoreAsRef   = new ArrayCollection();
        $this->referencedDocumentsStoreAsDbRef = new ArrayCollection();
    }
}

#[ODM\EmbeddedDocument]
class GH2825Embedded
{
    #[ODM\Field(name: 'renamed')]
    public string $property;

    #[ODM\EmbedOne(targetDocument: self::class)]
    public GH2825Embedded $embedded;

    #[ODM\ReferenceOne(targetDocument: GH2825Document::class, storeAs: ClassMetadata::REFERENCE_STORE_AS_ID)]
    public GH2825Document|null $referenceStoreAsId = null;

    #[ODM\ReferenceOne(targetDocument: GH2825Document::class, storeAs: ClassMetadata::REFERENCE_STORE_AS_REF)]
    public GH2825Document|null $referenceStoreAsRef = null;

    #[ODM\ReferenceOne(targetDocument: GH2825Document::class, storeAs: ClassMetadata::REFERENCE_STORE_AS_DB_REF)]
    public GH2825Document|null $referenceStoreAsDbRef = null;

    /** @var Collection<int, GH2825Embedded> */
    #[ODM\EmbedMany(targetDocument: self::class)]
    public Collection $embeddedDocuments;

    /** @var Collection<int, GH2825Document> */
    #[ODM\ReferenceMany(targetDocument: GH2825Document::class, storeAs: ClassMetadata::REFERENCE_STORE_AS_ID)]
    public Collection $referencedDocumentsStoreAsId;

    /** @var Collection<int, GH2825Document> */
    #[ODM\ReferenceMany(targetDocument: GH2825Document::class, storeAs: ClassMetadata::REFERENCE_STORE_AS_REF)]
    public Collection $referencedDocumentsStoreAsRef;

    /** @var Collection<int, GH2825Document> */
    #[ODM\ReferenceMany(targetDocument: GH2825Document::class, storeAs: ClassMetadata::REFERENCE_STORE_AS_DB_REF)]
    public Collection $referencedDocumentsStoreAsDbRef;

    public function __construct(string $property)
    {
        $this->property                        = $property;
        $this->embeddedDocuments               = new ArrayCollection();
        $this->referencedDocumentsStoreAsId    = new ArrayCollection();
        $this->referencedDocumentsStoreAsRef   = new ArrayCollection();
        $this->referencedDocumentsStoreAsDbRef = new ArrayCollection();
    }
}
