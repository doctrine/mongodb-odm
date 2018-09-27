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
    /** @var CommandLogger */
    private $logger;

    public function setUp()
    {
        parent::setUp();

        $this->logger = new CommandLogger();
        $this->logger->register();
    }

    public function tearDown()
    {
        $this->logger->unregister();

        return parent::tearDown();
    }

    public function testUpdatingDocumentBeforeItsInsertionShouldNotEntailMultipleQueries()
    {
        $listener = new GH1138Listener();
        $this->dm->getEventManager()->addEventListener(Events::onFlush, $listener);

        $doc = new GH1138Document();
        $doc->name = 'foo';

        $this->dm->persist($doc);
        $this->dm->flush();

        $this->assertCount(1, $this->logger, 'Changing a document before its insertion requires one query');
        $this->assertEquals('foo-changed', $doc->name);
        $this->assertEquals(1, $listener->inserts);
        $this->assertEquals(0, $listener->updates);
    }
}

/** @ODM\Document */
class GH1138Document
{
    public const CLASSNAME = __CLASS__;

    /** @ODM\Id */
    public $id;

    /** @ODM\Field(type="string") */
    public $name;
}

class GH1138Listener
{
    public $inserts = 0;
    public $updates = 0;

    public function onFlush(OnFlushEventArgs $args)
    {
        $dm = $args->getDocumentManager();
        $uow = $dm->getUnitOfWork();

        foreach ($uow->getScheduledDocumentInsertions() as $document) {
            $this->inserts++;
            if (! ($document instanceof GH1138Document)) {
                continue;
            }

            $document->name .= '-changed';
            $cm = $dm->getClassMetadata(GH1138Document::CLASSNAME);
            $uow->recomputeSingleDocumentChangeSet($cm, $document);
        }

        foreach ($uow->getScheduledDocumentUpdates() as $document) {
            $this->updates++;
        }
    }
}
