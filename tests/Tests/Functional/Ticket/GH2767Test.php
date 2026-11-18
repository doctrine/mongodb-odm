<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\Event\OnFlushEventArgs;
use Doctrine\ODM\MongoDB\Events;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Tests\BaseTestCase;

class GH2767Test extends BaseTestCase
{
    public function testChangeRemovalToUpdateAppliesEmbeddedDocumentUpdates(): void
    {
        $this->dm->getEventManager()->addEventListener([Events::onFlush], new GH2767Listener());

        $document = new GH2767Document([new GH2767EmbeddedDocument()]);
        $this->dm->persist($document);
        $this->dm->flush();

        $documentId = $document->id;
        $this->dm->clear();

        $document = $this->dm->find(GH2767Document::class, $documentId);
        $this->dm->remove($document);
        $this->dm->flush();

        $this->dm->clear();

        $document = $this->dm->find(GH2767Document::class, $documentId);

        // The removal was changed to an update in the onFlush listener, so the
        // document must still exist with the counters incremented.
        self::assertNotNull($document);
        self::assertSame(1, $document->removedCount);
        self::assertSame(1, $document->embeddedDocuments->first()->removedCount);
    }
}

class GH2767Listener
{
    public function onFlush(OnFlushEventArgs $eventArgs): void
    {
        $dm  = $eventArgs->getDocumentManager();
        $uow = $dm->getUnitOfWork();

        foreach ($uow->getScheduledDocumentDeletions() as $document) {
            if ($document instanceof GH2767Document) {
                $document->incrementRemovedCount();
                $dm->persist($document);
                $uow->recomputeSingleDocumentChangeSet($dm->getClassMetadata(GH2767Document::class), $document);
            }
        }
    }
}

#[ODM\Document]
class GH2767Document
{
    #[ODM\Id]
    public ?string $id = null;

    #[ODM\Field(type: 'int')]
    public int $removedCount = 0;

    /** @var Collection<int, GH2767EmbeddedDocument> */
    #[ODM\EmbedMany(targetDocument: GH2767EmbeddedDocument::class)]
    public Collection $embeddedDocuments;

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
class GH2767EmbeddedDocument
{
    #[ODM\Field(type: 'int')]
    public int $removedCount = 0;
}
