<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Event\LifecycleEventArgs;
use Doctrine\ODM\MongoDB\Events;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Tests\BaseTest;

class MODM67Test extends BaseTest
{
    /** @var MODM67TestEventListener */
    private $listener;

    private function getDocumentManager()
    {
        $this->listener = new MODM67TestEventListener($this->dm);
        $evm            = $this->dm->getEventManager();
        $events         = [
            Events::prePersist,
            Events::postPersist,
            Events::preUpdate,
            Events::postUpdate,
        ];
        $evm->addEventListener($events, $this->listener);

        return $this->dm;
    }

    public function testDerivedClassListener()
    {
        $dm = $this->getDocumentManager();

        $testDoc           = new MODM67DerivedClass();
        $testDoc->embedOne = new MODM67EmbeddedObject();

        $dm->persist($testDoc);
        $dm->flush();

        $this->assertTrue($testDoc->embedOne->prePersist);
        $this->assertTrue($testDoc->embedOne->postPersist);

        $this->assertFalse($testDoc->embedOne->preUpdate);
        $this->assertFalse($testDoc->embedOne->postUpdate);

        $dm->clear();

        $testDoc                        = $dm->find(MODM67DerivedClass::class, $testDoc->id);
        $testDoc->embedOne->numAccesses = 1;
        $dm->flush();

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
        if (! ($document instanceof MODM67EmbeddedObject)) {
            return;
        }

        $document->prePersist = true;
    }

    public function postPersist(LifecycleEventArgs $eventArgs)
    {
        $document = $eventArgs->getDocument();
        if (! ($document instanceof MODM67EmbeddedObject)) {
            return;
        }

        $document->postPersist = true;
    }

    public function preUpdate(LifecycleEventArgs $eventArgs)
    {
        $document = $eventArgs->getDocument();
        if (! ($document instanceof MODM67EmbeddedObject)) {
            return;
        }

        $document->preUpdate = true;
    }

    public function postUpdate(LifecycleEventArgs $eventArgs)
    {
        $document = $eventArgs->getDocument();
        if (! ($document instanceof MODM67EmbeddedObject)) {
            return;
        }

        $document->postUpdate = true;
    }
}

/**
 * @ODM\Document
 */
class MODM67DerivedClass
{
    /** @ODM\Id */
    public $id;

    /** @ODM\EmbedOne(targetDocument=MODM67EmbeddedObject::class) */
    public $embedOne;
}

/**
 * @ODM\EmbeddedDocument
 */
class MODM67EmbeddedObject
{
    /** @ODM\Field(type="int") */
    public $numAccesses = 0;

    /** @ODM\Field(type="bool") */
    public $prePersist = false;

    /** @ODM\Field(type="bool") */
    public $postPersist = false;

    /** @ODM\Field(type="bool") */
    public $preUpdate = false;

    /** @ODM\Field(type="bool") */
    public $postUpdate = false;
}
