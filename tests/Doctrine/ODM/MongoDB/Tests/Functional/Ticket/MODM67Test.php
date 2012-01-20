<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\ODM\MongoDB\Events;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Event\LifecycleEventArgs;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

class MODM67Test extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    private function getDocumentManager()
    {
        $this->listener = new MODM67TestEventListener($this->dm);
        $evm = $this->dm->getEventManager();
        $events = array(
            Events::prePersist,
            Events::postPersist,
            Events::preUpdate,
            Events::postUpdate,
        );
        $evm->addEventListener($events, $this->listener);

        return $this->dm;
    }

    public function testDerivedClassListener()
    {
        $dm = $this->getDocumentManager();

        $testDoc = new MODM67DerivedClass();
        $testDoc->embedOne = new MODM67EmbeddedObject();

        $dm->persist($testDoc);
        $dm->flush(null, array('safe' => true));

        $this->assertTrue($testDoc->embedOne->prePersist);
        $this->assertTrue($testDoc->embedOne->postPersist);

        $this->assertFalse($testDoc->embedOne->preUpdate);
        $this->assertFalse($testDoc->embedOne->postUpdate);

        $dm->clear();

        $testDoc = $dm->find(__NAMESPACE__ . '\MODM67DerivedClass', $testDoc->id);
        $testDoc->embedOne->numAccesses = 1;
        $dm->flush(null, array('safe' => true));

        $this->assertTrue($testDoc->embedOne->preUpdate);
        $this->assertTrue($testDoc->embedOne->postUpdate);
    }
}

class MODM67TestEventListener
{
    public $documentManager;

    public function __construct(DocumentManager $documentManager)
    {
        $this->documentManager = $documentManager;
    }

    public function prePersist(LifecycleEventArgs $eventArgs)
    {
        $document = $eventArgs->getDocument();
        if ($document instanceof MODM67EmbeddedObject) {
            $document->prePersist = true;
        }
    }

    public function postPersist(LifecycleEventArgs $eventArgs)
    {
        $document = $eventArgs->getDocument();
        if ($document instanceof MODM67EmbeddedObject) {
            $document->postPersist = true;
        }
    }

    public function preUpdate(LifecycleEventArgs $eventArgs)
    {
        $document = $eventArgs->getDocument();
        if ($document instanceof MODM67EmbeddedObject) {
            $document->preUpdate = true;
        }
    }

    public function postUpdate(LifecycleEventArgs $eventArgs)
    {
        $document = $eventArgs->getDocument();
        if ($document instanceof MODM67EmbeddedObject) {
            $document->postUpdate = true;
        }
    }
}

/**
 * @ODM\Document
 */
class MODM67DerivedClass
{
    /** @ODM\Id */
    public $id;

    /** @ODM\EmbedOne(targetDocument="MODM67EmbeddedObject") */
    public $embedOne;
}

/**
 * @ODM\EmbeddedDocument
 */
class MODM67EmbeddedObject
{
    /** @ODM\Int */
    public $numAccesses = 0;

    /** @ODM\Boolean */
    public $prePersist = false;

    /** @ODM\Boolean */
    public $postPersist = false;

    /** @ODM\Boolean */
    public $preUpdate = false;

    /** @ODM\Boolean */
    public $postUpdate = false;
}