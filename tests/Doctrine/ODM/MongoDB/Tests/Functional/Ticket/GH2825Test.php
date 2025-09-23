<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\Tests\BaseTestCase;
use MongoDB\BSON\ObjectId;

class GH2825Test extends BaseTestCase
{
    public function testQueryBuilderUpdatesEmbeddedDocumentCorrectly(): void
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

    public function testQueryBuilderUpdatesReferencesCorrectly(): void
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

    public function __construct(string $name)
    {
        $this->name = $name;
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

    public function __construct(string $property)
    {
        $this->property = $property;
    }
}
