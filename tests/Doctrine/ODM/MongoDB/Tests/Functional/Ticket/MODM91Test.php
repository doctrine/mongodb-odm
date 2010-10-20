<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

require_once __DIR__ . '/../../../../../../TestInit.php';

use Doctrine\ODM\MongoDB\ODMEvents;

class MODM91Test extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    private function getDocumentManager()
    {
        $this->listener = new MODM91EventListener();
        $evm = $this->dm->getEventManager();
        $events = array(
            ODMEvents::preUpdate,
            ODMEvents::postUpdate,
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

        $testDoc = $dm->findOne(__NAMESPACE__.'\MODM91TestDocument');
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

/** @Document */
class MODM91TestDocument
{
    /** @Id */
    public $id;

    /** @String */
    public $name;

    /** @EmbedOne(targetDocument="MODM91TestEmbeddedDocument") */
    public $embedded;
}

/** @EmbeddedDocument */
class MODM91TestEmbeddedDocument
{
    /** @String */
    public $name;
}