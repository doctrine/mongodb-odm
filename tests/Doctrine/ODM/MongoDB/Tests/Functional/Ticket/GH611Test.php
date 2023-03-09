<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Tests\BaseTestCase;
use MongoDB\BSON\ObjectId;

class GH611Test extends BaseTestCase
{
    public function testPreparationofEmbeddedDocumentValues(): void
    {
        $documentId = (string) (new ObjectId());

        $document           = new GH611Document();
        $document->id       = $documentId;
        $document->embedded = new GH611EmbeddedDocument(1, 'a');

        $this->dm->persist($document);
        $this->dm->flush();
        $this->dm->clear();

        $document = $this->dm->find(GH611Document::class, $documentId);

        self::assertSame($documentId, $document->id);
        self::assertSame(1, $document->embedded->id);
        self::assertSame('a', $document->embedded->name);

        // Update the embedded document's ID field via change tracking
        $document->embedded->id = 2;
        $this->dm->flush();
        $this->dm->clear();

        $document = $this->dm->find(GH611Document::class, $documentId);

        self::assertSame($documentId, $document->id);
        self::assertSame(2, $document->embedded->id);

        // Update the entire embedded document via change tracking
        $document->embedded = new GH611EmbeddedDocument(3, 'b');
        $this->dm->flush();
        $this->dm->clear();

        $document = $this->dm->find(GH611Document::class, $documentId);

        self::assertSame($documentId, $document->id);
        self::assertSame(3, $document->embedded->id);
        self::assertSame('b', $document->embedded->name);

        // Update the embedded document's ID field via query builder
        $query = $this->dm->createQueryBuilder(GH611Document::class)
            ->updateOne()
            ->field('id')->equals($documentId)
            ->field('embedded._id')->exists(false)
            ->field('embedded.id')->set(4)
            ->getQuery()
            ->execute();

        $this->dm->clear();

        $document = $this->dm->find(GH611Document::class, $documentId);

        self::assertSame($documentId, $document->id);
        self::assertSame(4, $document->embedded->id);
        self::assertSame('b', $document->embedded->name);

        // Update the entire embedded document with an array via query builder
        $query = $this->dm->createQueryBuilder(GH611Document::class)
            ->updateOne()
            ->field('id')->equals($documentId)
            ->field('embedded._id')->exists(false)
            ->field('embedded')->set(['id' => 5, 'n' => 'c'])
            ->getQuery()
            ->execute();

        $this->dm->clear();

        $document = $this->dm->find(GH611Document::class, $documentId);

        self::assertSame($documentId, $document->id);
        self::assertSame(5, $document->embedded->id);
        self::assertSame('c', $document->embedded->name);

        // Update the entire embedded document with an unmapped object via query builder
        $query = $this->dm->createQueryBuilder(GH611Document::class)
            ->updateOne()
            ->field('id')->equals($documentId)
            ->field('embedded._id')->exists(false)
            ->field('embedded')->set((object) ['id' => 6, 'n' => 'd'])
            ->getQuery()
            ->execute();

        $this->dm->clear();

        $document = $this->dm->find(GH611Document::class, $documentId);

        self::assertSame($documentId, $document->id);
        self::assertSame(6, $document->embedded->id);
        self::assertSame('d', $document->embedded->name);

        // Update the entire embedded document with a mapped object via query builder
        $query = $this->dm->createQueryBuilder(GH611Document::class)
            ->updateOne()
            ->field('id')->equals($documentId)
            ->field('embedded._id')->exists(false)
            ->field('embedded')->set(new GH611EmbeddedDocument(7, 'e'))
            ->getQuery()
            ->execute();

        $this->dm->clear();

        $document = $this->dm->find(GH611Document::class, $documentId);

        self::assertSame($documentId, $document->id);
        self::assertSame(7, $document->embedded->id);
        self::assertSame('e', $document->embedded->name);
    }
}

/** @ODM\Document */
class GH611Document
{
    /**
     * @ODM\Id
     *
     * @var string|null
     */
    public $id;

    /**
     * @ODM\EmbedOne(targetDocument=GH611EmbeddedDocument::class)
     *
     * @var GH611EmbeddedDocument|null
     */
    public $embedded;
}

/** @ODM\EmbeddedDocument */
class GH611EmbeddedDocument
{
    /**
     * @ODM\Field(type="int")
     *
     * @var int
     */
    public $id;

    /**
     * @ODM\Field(name="n", type="string")
     *
     * @var string
     */
    public $name;

    public function __construct(int $id, string $name)
    {
        $this->id   = $id;
        $this->name = $name;
    }
}
