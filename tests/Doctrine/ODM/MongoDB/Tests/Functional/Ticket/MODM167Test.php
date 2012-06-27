<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\ODM\MongoDB\Event\OnFlushEventArgs;
use Doctrine\ODM\MongoDB\Events;
use Documents\User;

class MODM167Test extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{

    public function setUp()
    {
        parent::setUp();

        $this->listener = new MODM167EventListener();
        $evm = $this->dm->getEventManager();
        $evm->addEventListener(Events::onFlush, $this->listener);
        return $this->dm;
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
        $test = $repository->find($test->getId());

        $this->assertNull($test);
    }
}

class MODM167EventListener
{
    public function onFlush(OnFlushEventArgs $eventArgs)
    {
        $documentManager = $eventArgs->getDocumentManager();
        $unitOfWork = $documentManager->getUnitOfWork();

        foreach ($unitOfWork->getScheduledDocumentInsertions() as $document) {
            $unitOfWork->detach($document);
        }
    }
}