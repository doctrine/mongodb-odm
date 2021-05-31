<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\ODM\MongoDB\Event\OnFlushEventArgs;
use Doctrine\ODM\MongoDB\Events;
use Doctrine\ODM\MongoDB\Tests\BaseTest;
use Documents\User;

use function get_class;

class MODM167Test extends BaseTest
{
    /** @var MODM167EventListener */
    private $listener;

    public function setUp(): void
    {
        parent::setUp();

        $this->listener = new MODM167EventListener();
        $evm            = $this->dm->getEventManager();
        $evm->addEventListener(Events::onFlush, $this->listener);
    }

    public function testDetatchNewDocumentDuringOnFlush()
    {
        // create a test document
        $test = new User();
        $test->setUsername('toby');

        $this->dm->persist($test);
        $this->dm->flush();
        $this->dm->clear();

        $repository = $this->dm->getRepository(get_class($test));
        $test       = $repository->find($test->getId());

        $this->assertNull($test);
    }
}

class MODM167EventListener
{
    public function onFlush(OnFlushEventArgs $eventArgs)
    {
        $documentManager = $eventArgs->getDocumentManager();
        $unitOfWork      = $documentManager->getUnitOfWork();

        foreach ($unitOfWork->getScheduledDocumentInsertions() as $document) {
            $unitOfWork->detach($document);
        }
    }
}
