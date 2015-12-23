<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\ODM\MongoDB\Events;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

class MODM83Test extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    private function getDocumentManager()
    {
        $this->listener = new MODM83EventListener();
        $evm = $this->dm->getEventManager();
        $events = [
            Events::preUpdate,
            Events::postUpdate,
        ];
        $evm->addEventListener($events, $this->listener);
        return $this->dm;
    }

    public function testDocumentWithEmbeddedDocumentNotUpdated()
    {
        $dm = $this->getDocumentManager();

        $won = new MODM83TestDocument();
        $won->name = 'Parent';
        $won->embedded = new MODM83TestEmbeddedDocument();
        $won->embedded->name = 'Child';
        $too = new MODM83OtherDocument();
        $too->name = 'Neighbor';
        $dm->persist($won);
        $dm->persist($too);
        $dm->flush();
        $dm->clear();

        $won = $dm->find(__NAMESPACE__.'\MODM83TestDocument', $won->id);
        $too = $dm->find(__NAMESPACE__.'\MODM83OtherDocument', $too->id);
        $too->name = 'Bob';
        $dm->flush();
        $dm->clear();

        $called = [
            Events::preUpdate  => [__NAMESPACE__.'\MODM83OtherDocument'],
            Events::postUpdate => [__NAMESPACE__.'\MODM83OtherDocument']
        ];
        $this->assertEquals($called, $this->listener->called);
    }
}

class MODM83EventListener
{
    public $called = [];
    public function __call($method, $args)
    {
        $document = $args[0]->getDocument();
        $className = get_class($document);
        $this->called[$method][] = $className;
    }
}

/** @ODM\Document */
class MODM83TestDocument
{
    /** @ODM\Id */
    public $id;

    /** @ODM\String */
    public $name;

    /** @ODM\EmbedOne(targetDocument="MODM83TestEmbeddedDocument") */
    public $embedded;
}

/** @ODM\EmbeddedDocument */
class MODM83TestEmbeddedDocument
{
    /** @ODM\String */
    public $name;
}

/** @ODM\Document */
class MODM83OtherDocument
{
    /** @ODM\Id */
    public $id;

    /** @ODM\String */
    public $name;
}
