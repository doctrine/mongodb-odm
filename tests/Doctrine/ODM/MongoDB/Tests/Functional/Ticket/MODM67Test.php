<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Event\LifecycleEventArgs;
use Doctrine\ODM\MongoDB\Events;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Tests\BaseTestCase;

class MODM67Test extends BaseTestCase
{
    private MODM67TestEventListener $listener;

    private function getDocumentManager(): ?DocumentManager
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

    public function testDerivedClassListener(): void
    {
        $dm = $this->getDocumentManager();

        $testDoc           = new MODM67DerivedClass();
        $testDoc->embedOne = new MODM67EmbeddedObject();

        $dm->persist($testDoc);
        $dm->flush();

        self::assertTrue($testDoc->embedOne->prePersist);
        self::assertTrue($testDoc->embedOne->postPersist);

        self::assertFalse($testDoc->embedOne->preUpdate);
        self::assertFalse($testDoc->embedOne->postUpdate);

        $dm->clear();

        $testDoc                        = $dm->find(MODM67DerivedClass::class, $testDoc->id);
        $testDoc->embedOne->numAccesses = 1;
        $dm->flush();

        self::assertTrue($testDoc->embedOne->preUpdate);
        self::assertTrue($testDoc->embedOne->postUpdate);
    }
}

class MODM67TestEventListener
{
    /** @var DocumentManager */
    public $documentManager;

    public function __construct(DocumentManager $documentManager)
    {
        $this->documentManager = $documentManager;
    }

    public function prePersist(LifecycleEventArgs $eventArgs): void
    {
        $document = $eventArgs->getDocument();
        if (! ($document instanceof MODM67EmbeddedObject)) {
            return;
        }

        $document->prePersist = true;
    }

    public function postPersist(LifecycleEventArgs $eventArgs): void
    {
        $document = $eventArgs->getDocument();
        if (! ($document instanceof MODM67EmbeddedObject)) {
            return;
        }

        $document->postPersist = true;
    }

    public function preUpdate(LifecycleEventArgs $eventArgs): void
    {
        $document = $eventArgs->getDocument();
        if (! ($document instanceof MODM67EmbeddedObject)) {
            return;
        }

        $document->preUpdate = true;
    }

    public function postUpdate(LifecycleEventArgs $eventArgs): void
    {
        $document = $eventArgs->getDocument();
        if (! ($document instanceof MODM67EmbeddedObject)) {
            return;
        }

        $document->postUpdate = true;
    }
}

#[ODM\Document]
class MODM67DerivedClass
{
    /** @var string|null */
    #[ODM\Id]
    public $id;

    /** @var MODM67EmbeddedObject|null */
    #[ODM\EmbedOne(targetDocument: MODM67EmbeddedObject::class)]
    public $embedOne;
}

#[ODM\EmbeddedDocument]
class MODM67EmbeddedObject
{
    /** @var int|null */
    #[ODM\Field(type: 'int')]
    public $numAccesses = 0;

    /** @var bool|null */
    #[ODM\Field(type: 'bool')]
    public $prePersist = false;

    /** @var bool|null */
    #[ODM\Field(type: 'bool')]
    public $postPersist = false;

    /** @var bool|null */
    #[ODM\Field(type: 'bool')]
    public $preUpdate = false;

    /** @var bool|null */
    #[ODM\Field(type: 'bool')]
    public $postUpdate = false;
}
