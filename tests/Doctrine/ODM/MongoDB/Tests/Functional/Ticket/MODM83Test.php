<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Event\LifecycleEventArgs;
use Doctrine\ODM\MongoDB\Events;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Tests\BaseTest;

class MODM83Test extends BaseTest
{
    private MODM83EventListener $listener;

    private function getDocumentManager(): ?DocumentManager
    {
        $this->listener = new MODM83EventListener();
        $evm            = $this->dm->getEventManager();
        $events         = [
            Events::preUpdate,
            Events::postUpdate,
        ];
        $evm->addEventListener($events, $this->listener);

        return $this->dm;
    }

    public function testDocumentWithEmbeddedDocumentNotUpdated(): void
    {
        $dm = $this->getDocumentManager();

        $won                 = new MODM83TestDocument();
        $won->name           = 'Parent';
        $won->embedded       = new MODM83TestEmbeddedDocument();
        $won->embedded->name = 'Child';
        $too                 = new MODM83OtherDocument();
        $too->name           = 'Neighbor';
        $dm->persist($won);
        $dm->persist($too);
        $dm->flush();
        $dm->clear();

        $won       = $dm->find(MODM83TestDocument::class, $won->id);
        $too       = $dm->find(MODM83OtherDocument::class, $too->id);
        $too->name = 'Bob';
        $dm->flush();
        $dm->clear();

        $called = [
            Events::preUpdate  => [MODM83OtherDocument::class],
            Events::postUpdate => [MODM83OtherDocument::class],
        ];
        self::assertEquals($called, $this->listener->called);
    }
}

class MODM83EventListener
{
    /** @var array<string, class-string[]> */
    public $called = [];

    /** @param array{LifecycleEventArgs} $args */
    public function __call(string $method, array $args): void
    {
        $document                = $args[0]->getDocument();
        $className               = $document::class;
        $this->called[$method][] = $className;
    }
}

/** @ODM\Document */
class MODM83TestDocument
{
    /**
     * @ODM\Id
     *
     * @var string|null
     */
    public $id;

    /**
     * @ODM\Field(type="string")
     *
     * @var string|null
     */
    public $name;

    /**
     * @ODM\EmbedOne(targetDocument=MODM83TestEmbeddedDocument::class)
     *
     * @var MODM83TestEmbeddedDocument|null
     */
    public $embedded;
}

/** @ODM\EmbeddedDocument */
class MODM83TestEmbeddedDocument
{
    /**
     * @ODM\Field(type="string")
     *
     * @var string|null
     */
    public $name;
}

/** @ODM\Document */
class MODM83OtherDocument
{
    /**
     * @ODM\Id
     *
     * @var string|null
     */
    public $id;

    /**
     * @ODM\Field(type="string")
     *
     * @var string|null
     */
    public $name;
}
