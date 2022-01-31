<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\ODM\MongoDB\Events;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Tests\BaseTest;

use function get_class;

class MODM83Test extends BaseTest
{
    /** @var MODM83EventListener */
    private $listener;

    private function getDocumentManager()
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

    public function testDocumentWithEmbeddedDocumentNotUpdated()
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
        $this->assertEquals($called, $this->listener->called);
    }
}

class MODM83EventListener
{
    public $called = [];

    public function __call($method, $args)
    {
        $document                = $args[0]->getDocument();
        $className               = get_class($document);
        $this->called[$method][] = $className;
    }
}

/** @ODM\Document */
class MODM83TestDocument
{
    /** @ODM\Id */
    public $id;

    /** @ODM\Field(type="string") */
    public $name;

    /** @ODM\EmbedOne(targetDocument=MODM83TestEmbeddedDocument::class) */
    public $embedded;
}

/** @ODM\EmbeddedDocument */
class MODM83TestEmbeddedDocument
{
    /** @ODM\Field(type="string") */
    public $name;
}

/** @ODM\Document */
class MODM83OtherDocument
{
    /** @ODM\Id */
    public $id;

    /** @ODM\Field(type="string") */
    public $name;
}
