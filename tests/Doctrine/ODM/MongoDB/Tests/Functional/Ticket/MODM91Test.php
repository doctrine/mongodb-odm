<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\ODM\MongoDB\Events;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

class MODM91Test extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    private function getDocumentManager()
    {
        $this->listener = new MODM91EventListener();
        $evm = $this->dm->getEventManager();
        $events = array(
            Events::preUpdate,
            Events::postUpdate,
        );
        $evm->addEventListener($events, $this->listener);
        return $this->dm;
    }

    public function testDocumentWithEmbeddedDocumentNotUpdated()
    {
        $dm = $this->getDocumentManager();

        $testDoc = new MODM91TestDocument();
        $testDoc->name = 'test doc';
        $testDoc->embedded = new MODM91TestEmbeddedDocument();

        $dm->persist($testDoc);
        $dm->flush();
        $dm->clear();

        $testDoc = $dm->find(__NAMESPACE__.'\MODM91TestDocument', $testDoc->id);
        $dm->flush();
        $dm->clear();

        $called = array();
        $this->assertEquals($called, $this->listener->called);
    }
}

class MODM91EventListener
{
    public $called = array();
    public function __call($method, $args)
    {
        $document = $args[0]->getDocument();
        $className = get_class($document);
        $this->called[$method][] = $className;
    }
}

/** @ODM\Document */
class MODM91TestDocument
{
    /** @ODM\Id */
    public $id;

    /** @ODM\Field(type="string") */
    public $name;

    /** @ODM\EmbedOne(targetDocument="MODM91TestEmbeddedDocument") */
    public $embedded;
}

/** @ODM\EmbeddedDocument */
class MODM91TestEmbeddedDocument
{
    /** @ODM\Field(type="string") */
    public $name;
}
