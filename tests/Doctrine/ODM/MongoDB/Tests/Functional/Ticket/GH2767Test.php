<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Tests\BaseTestCase;

class GH2767Test extends BaseTestCase
{
    public function testRemovePersistEmbedded(): void
    {
        // Start with a parent document and 1 embedded document, both with removedCount = 0
        $document = new TestDocument([new TestEmbeddedDocument()]);
        $this->dm->persist($document);
        $this->dm->flush();
        $id = $document->id;

        // Remove the document
        $this->dm->remove($document);

        // Increase the removedCount of the parent document and the embedded document by 1
        $document->incrementRemovedCount();

        // Re-persist the same document
        $this->dm->persist($document);

        $this->dm->flush();
        $this->dm->clear();

        $repository = $this->dm->getRepository(TestDocument::class);
        $result     = $repository->find($id);

        self::assertEquals(1, $result->removedCount);
        self::assertEquals(1, $result->embeddedDocuments[0]->removedCount);
    }
}

#[ODM\Document]
class TestDocument
{
    #[ODM\Id]
    public ?string $id = null;

    #[ODM\Field(type: 'int')]
    public int $removedCount = 0;

    /** @var Collection<int, TestEmbeddedDocument> */
    #[ODM\EmbedMany(targetDocument: TestEmbeddedDocument::class)]
    public $embeddedDocuments;

    /** @param TestEmbeddedDocument[] $embeddedDocuments */
    public function __construct(array $embeddedDocuments)
    {
        $this->embeddedDocuments = new ArrayCollection($embeddedDocuments);
    }

    public function incrementRemovedCount(): void
    {
        $this->removedCount++;
        foreach ($this->embeddedDocuments as $embeddedDocument) {
            $embeddedDocument->removedCount++;
        }
    }
}

#[ODM\EmbeddedDocument]
class TestEmbeddedDocument
{
    #[ODM\Field(type: 'int')]
    public int $removedCount = 0;
}
