<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\ODM\MongoDB\Event\OnFlushEventArgs;
use Doctrine\ODM\MongoDB\Events;
use Doctrine\ODM\MongoDB\Tests\BaseTest;
use Documents\Phonenumber;
use Documents\User;

use function get_class;
use function sort;

class MODM166Test extends BaseTest
{
    /** @var MODM166EventListener */
    private $listener;

    public function setUp(): void
    {
        parent::setUp();

        $this->listener = new MODM166EventListener();
        $evm            = $this->dm->getEventManager();
        $evm->addEventListener(Events::onFlush, $this->listener);
    }

    public function testUpdateCollectionDuringOnFlushAndRecomputSingleDocumentChangeSet()
    {
        // create a test document
        $test = new User();
        $test->setUsername('toby');
        $test->addPhonenumber(new Phonenumber('1111'));

        $this->dm->persist($test);
        $this->dm->flush();

        $test->setUsername('lucy');
        $this->dm->flush();
        $this->dm->clear();

        $repository = $this->dm->getRepository(get_class($test));
        $test       = $repository->findOneBy(['username' => 'lucy']);

        $phonenumbers = [];
        foreach ($test->getPhonenumbers() as $phonenumber) {
            $phonenumbers[] = $phonenumber->getPhonenumber();
        }

        sort($phonenumbers);

        $this->assertEquals(['1111', '2222'], $phonenumbers);
    }
}

class MODM166EventListener
{
    public function onFlush(OnFlushEventArgs $eventArgs)
    {
        $documentManager = $eventArgs->getDocumentManager();
        $unitOfWork      = $documentManager->getUnitOfWork();

        foreach ($unitOfWork->getScheduledDocumentUpdates() as $document) {
            $metadata = $documentManager->getClassMetadata(get_class($document));
            $document->addPhonenumber(new Phonenumber('2222'));
            $unitOfWork->recomputeSingleDocumentChangeSet($metadata, $document);
        }
    }
}
