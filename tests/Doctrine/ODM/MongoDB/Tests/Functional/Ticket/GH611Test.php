<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use MongoDB\BSON\ObjectId;

class GH611Test extends BaseTest
{
    public function testPreparationofEmbeddedDocumentValues()
    {
        $documentId = (string) (new ObjectId());

        $document = new GH611Document();
        $document->id = $documentId;
        $document->embedded = new GH611EmbeddedDocument(1, 'a');

        $this->dm->persist($document);
        $this->dm->flush();
        $this->dm->clear();

        $document = $this->dm->find(GH611Document::class, $documentId);

        $this->assertSame($documentId, $document->id);
        $this->assertSame(1, $document->embedded->id);
        $this->assertSame('a', $document->embedded->name);

        // Update the embedded document's ID field via change tracking
        $document->embedded->id = 2;
        $this->dm->flush();
        $this->dm->clear();

        $document = $this->dm->find(GH611Document::class, $documentId);

        $this->assertSame($documentId, $document->id);
        $this->assertSame(2, $document->embedded->id);

        // Update the entire embedded document via change tracking
        $document->embedded = new GH611EmbeddedDocument(3, 'b');
        $this->dm->flush();
        $this->dm->clear();

        $document = $this->dm->find(GH611Document::class, $documentId);

        $this->assertSame($documentId, $document->id);
        $this->assertSame(3, $document->embedded->id);
        $this->assertSame('b', $document->embedded->name);

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

        $this->assertSame($documentId, $document->id);
        $this->assertSame(4, $document->embedded->id);
        $this->assertSame('b', $document->embedded->name);

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

        $this->assertSame($documentId, $document->id);
        $this->assertSame(5, $document->embedded->id);
        $this->assertSame('c', $document->embedded->name);

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

        $this->assertSame($documentId, $document->id);
        $this->assertSame(6, $document->embedded->id);
        $this->assertSame('d', $document->embedded->name);

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

        $this->assertSame($documentId, $document->id);
        $this->assertSame(7, $document->embedded->id);
        $this->assertSame('e', $document->embedded->name);
    }
}

/** @ODM\Document */
class GH611Document
{
    /** @ODM\Id */
    public $id;

    /** @ODM\EmbedOne(targetDocument=GH611EmbeddedDocument::class) */
    public $embedded;
}

/** @ODM\EmbeddedDocument */
class GH611EmbeddedDocument
{
    /** @ODM\Field(type="int") */
    public $id;

    /** @ODM\Field(name="n", type="string") */
    public $name;

    public function __construct($id, $name)
    {
        $this->id = $id;
        $this->name = $name;
    }
}
