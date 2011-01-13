<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\ODM\MongoDB\Events;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Event\LifecycleEventArgs;

class MODM67Test extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    private function getDocumentManager()
    {
        $this->listener = new MODM67TestEventListener($this->dm);
        $evm = $this->dm->getEventManager();
        $events = array(
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

        $this->assertFalse($testDoc->embedOne->isUpdated);

        $dm->persist($testDoc);
        $dm->flush(array('safe' => true));
        $dm->clear();

        $testDoc = $dm->find(__NAMESPACE__ . '\MODM67DerivedClass', $testDoc->id);
        $testDoc->embedOne->numAccesses = 1;
        $dm->flush(array('safe' => true));

        $this->assertTrue($testDoc->embedOne->isUpdated);
    }
}

class MODM67TestEventListener
{
    public $documentManager;

    public function __construct(DocumentManager $documentManager)
    {
        $this->documentManager = $documentManager;
    }

    public function postUpdate(LifecycleEventArgs $eventArgs)
    {
        $document = $eventArgs->getDocument();

        if ($document instanceof MODM67EmbeddedObject) {
            $document->isUpdated = true;
        }
    }
}

/**
 * @MappedSuperclass
 * @HasLifecycleCallbacks
 * @Document
 */
class MODM67BaseClass
{
    /** @Id */
    public $id;

    /** @EmbedOne(targetDocument="MODM67EmbeddedObject") */
    public $embedOneBase;

    public function __construct()
    {
    }
}

/**
 * @Document
 * @HasLifecycleCallbacks
 */
class MODM67DerivedClass extends MODM67BaseClass
{
    /** @EmbedOne(targetDocument="MODM67EmbeddedObject") */
    public $embedOne;

    public function __construct()
    {
        parent::__construct();
    }
}

/**
 * @EmbeddedDocument
 */
class MODM67EmbeddedObject
{
    /** @Int */
    public $numAccesses;

    /** @Boolean */
    public $isUpdated;

    public function __construct()
    {
        $this->numAccesses = 0;
        $this->isUpdated = false;
    }
}
