<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\ODM\MongoDB\Event\OnFlushEventArgs;
use Doctrine\ODM\MongoDB\Events;
use Doctrine\ODM\MongoDB\Tests\BaseTest;
use Documents\Phonenumber;
use Documents\User;

use function sort;

class MODM166Test extends BaseTest
{
    private MODM166EventListener $listener;

    public function setUp(): void
    {
        parent::setUp();

        $this->listener = new MODM166EventListener();
        $evm            = $this->dm->getEventManager();
        $evm->addEventListener(Events::onFlush, $this->listener);
    }

    public function testUpdateCollectionDuringOnFlushAndRecomputSingleDocumentChangeSet(): void
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

        $repository = $this->dm->getRepository($test::class);
        $test       = $repository->findOneBy(['username' => 'lucy']);

        $phonenumbers = [];
        foreach ($test->getPhonenumbers() as $phonenumber) {
            $phonenumbers[] = $phonenumber->getPhonenumber();
        }

        sort($phonenumbers);

        self::assertEquals(['1111', '2222'], $phonenumbers);
    }
}

class MODM166EventListener
{
    public function onFlush(OnFlushEventArgs $eventArgs): void
    {
        $documentManager = $eventArgs->getDocumentManager();
        $unitOfWork      = $documentManager->getUnitOfWork();

        foreach ($unitOfWork->getScheduledDocumentUpdates() as $document) {
            $metadata = $documentManager->getClassMetadata($document::class);
            $document->addPhonenumber(new Phonenumber('2222'));
            $unitOfWork->recomputeSingleDocumentChangeSet($metadata, $document);
        }
    }
}
