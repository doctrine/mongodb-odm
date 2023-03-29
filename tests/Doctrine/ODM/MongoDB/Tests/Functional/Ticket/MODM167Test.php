<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\ODM\MongoDB\Event\OnFlushEventArgs;
use Doctrine\ODM\MongoDB\Events;
use Doctrine\ODM\MongoDB\Tests\BaseTestCase;
use Documents\User;

class MODM167Test extends BaseTestCase
{
    private MODM167EventListener $listener;

    public function setUp(): void
    {
        parent::setUp();

        $this->listener = new MODM167EventListener();
        $evm            = $this->dm->getEventManager();
        $evm->addEventListener(Events::onFlush, $this->listener);
    }

    public function testDetatchNewDocumentDuringOnFlush(): void
    {
        // create a test document
        $test = new User();
        $test->setUsername('toby');

        $this->dm->persist($test);
        $this->dm->flush();
        $this->dm->clear();

        $repository = $this->dm->getRepository($test::class);
        $test       = $repository->find($test->getId());

        self::assertNull($test);
    }
}

class MODM167EventListener
{
    public function onFlush(OnFlushEventArgs $eventArgs): void
    {
        $documentManager = $eventArgs->getDocumentManager();
        $unitOfWork      = $documentManager->getUnitOfWork();

        foreach ($unitOfWork->getScheduledDocumentInsertions() as $document) {
            $unitOfWork->detach($document);
        }
    }
}
