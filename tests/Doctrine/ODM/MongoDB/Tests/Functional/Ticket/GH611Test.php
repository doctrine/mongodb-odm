<?php

namespace Doctrine\ODM\MongoDB\Tests;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

class GH611Test extends BaseTest
{
    public function testPreparationofEmbeddedDocumentValues()
    {
        $documentId = (string) (new \MongoId());
        $embeddedId = 1;

        $document = new GH611Document();
        $document->id = $documentId;
        $document->embedded = new GH611EmbeddedDocument($embeddedId);

        $this->dm->persist($document);
        $this->dm->flush();
        $this->dm->clear();

        $document = $this->dm->find(__NAMESPACE__ . '\GH611Document', $documentId);

        $this->assertSame($documentId, $document->id);
        $this->assertSame($embeddedId, $document->embedded->id);

        // Update the embedded document's ID field via change tracking
        $document->embedded->id = ++$embeddedId;
        $this->dm->flush();
        $this->dm->clear();

        $document = $this->dm->find(__NAMESPACE__ . '\GH611Document', $documentId);

        $this->assertSame($documentId, $document->id);
        $this->assertSame($embeddedId, $document->embedded->id);

        // Update the entire embedded document via change tracking
        $document->embedded = new GH611EmbeddedDocument(++$embeddedId);
        $this->dm->flush();
        $this->dm->clear();

        $document = $this->dm->find(__NAMESPACE__ . '\GH611Document', $documentId);

        $this->assertSame($documentId, $document->id);
        $this->assertSame($embeddedId, $document->embedded->id);

        // Update the embedded document's ID field via query builder
        $query = $this->dm->createQueryBuilder(__NAMESPACE__ . '\GH611Document')
            ->update()
            ->field('id')->equals($documentId)
            ->field('embedded._id')->exists(false)
            ->field('embedded.id')->set(++$embeddedId)
            ->getQuery()
            ->execute();

        $this->dm->clear();

        $document = $this->dm->find(__NAMESPACE__ . '\GH611Document', $documentId);

        $this->assertSame($documentId, $document->id);
        $this->assertSame($embeddedId, $document->embedded->id);

        // Update the entire embedded document with an array via query builder
        $query = $this->dm->createQueryBuilder(__NAMESPACE__ . '\GH611Document')
            ->update()
            ->field('id')->equals($documentId)
            ->field('embedded._id')->exists(false)
            ->field('embedded')->set(array('id' => ++$embeddedId))
            ->getQuery()
            ->execute();

        $this->dm->clear();

        $document = $this->dm->find(__NAMESPACE__ . '\GH611Document', $documentId);

        $this->assertSame($documentId, $document->id);
        $this->assertSame($embeddedId, $document->embedded->id);
    }
}

/** @ODM\Document */
class GH611Document
{
    /** @ODM\Id */
    public $id;

    /** @ODM\EmbedOne(targetDocument="GH611EmbeddedDocument") */
    public $embedded;
}

/** @ODM\EmbeddedDocument */
class GH611EmbeddedDocument
{
    /** @ODM\Int */
    public $id;

    public function __construct($id)
    {
        $this->id = $id;
    }
}
