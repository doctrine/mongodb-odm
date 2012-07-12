<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\ODM\MongoDB\Event\OnFlushEventArgs;
use Doctrine\ODM\MongoDB\Events;
use Documents\Phonenumber;
use Documents\User;

class MODM166Test extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{

    public function setUp()
    {
        parent::setUp();

        $this->listener = new MODM166EventListener();
        $evm = $this->dm->getEventManager();
        $evm->addEventListener(Events::onFlush, $this->listener);
        return $this->dm;
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
        $test = $repository->findOneBy(array('username' => 'lucy'));

        $phonenumbers = array();
        foreach ($test->getPhonenumbers() as $phonenumber){
            $phonenumbers[] = $phonenumber->getPhonenumber();
        }
        sort($phonenumbers);

        $this->assertEquals(array('1111', '2222'), $phonenumbers);
    }
}

class MODM166EventListener
{
    public function onFlush(OnFlushEventArgs $eventArgs)
    {
        $documentManager = $eventArgs->getDocumentManager();
        $unitOfWork = $documentManager->getUnitOfWork();

        foreach ($unitOfWork->getScheduledDocumentUpdates() as $document) {
            $metadata = $documentManager->getClassMetadata(get_class($document));
            $document->addPhonenumber(new Phonenumber('2222'));
            $unitOfWork->recomputeSingleDocumentChangeSet($metadata, $document);
        }
    }
}
