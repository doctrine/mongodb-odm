<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\ODM\MongoDB\APM\CommandLogger;
use Doctrine\ODM\MongoDB\Event\OnFlushEventArgs;
use Doctrine\ODM\MongoDB\Events;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Tests\BaseTest;

class GH1138Test extends BaseTest
{
    private CommandLogger $logger;

    public function setUp(): void
    {
        parent::setUp();

        $this->logger = new CommandLogger();
        $this->logger->register();
    }

    public function tearDown(): void
    {
        $this->logger->unregister();

        parent::tearDown();
    }

    public function testUpdatingDocumentBeforeItsInsertionShouldNotEntailMultipleQueries(): void
    {
        $listener = new GH1138Listener();
        $this->dm->getEventManager()->addEventListener(Events::onFlush, $listener);

        $doc       = new GH1138Document();
        $doc->name = 'foo';

        $this->dm->persist($doc);
        $this->dm->flush();

        self::assertCount(1, $this->logger, 'Changing a document before its insertion requires one query');
        self::assertEquals('foo-changed', $doc->name);
        self::assertEquals(1, $listener->inserts);
        self::assertEquals(0, $listener->updates);
    }
}

/** @ODM\Document */
class GH1138Document
{
    /**
     * @ODM\Id
     *
     * @var string|null
     */
    public $id;

    /**
     * @ODM\Field(type="string")
     *
     * @var string|null
     */
    public $name;
}

class GH1138Listener
{
    /** @var int */
    public $inserts = 0;

    /** @var int */
    public $updates = 0;

    public function onFlush(OnFlushEventArgs $args): void
    {
        $dm  = $args->getDocumentManager();
        $uow = $dm->getUnitOfWork();

        foreach ($uow->getScheduledDocumentInsertions() as $document) {
            $this->inserts++;
            if (! ($document instanceof GH1138Document)) {
                continue;
            }

            $document->name .= '-changed';
            $cm              = $dm->getClassMetadata(GH1138Document::class);
            $uow->recomputeSingleDocumentChangeSet($cm, $document);
        }

        foreach ($uow->getScheduledDocumentUpdates() as $document) {
            $this->updates++;
        }
    }
}
