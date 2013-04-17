<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\ODM\MongoDB\Events;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

class GH560Test extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    private function getDocumentManager()
    {
        $this->listener = new GH560EventListener();
        $evm = $this->dm->getEventManager();
        $events = array(
            Events::prePersist,
            Events::postPersist,
            Events::preUpdate,
            Events::postUpdate,
            Events::preLoad,
            Events::postLoad,
            Events::preRemove,
            Events::postRemove
        );
        $evm->addEventListener($events, $this->listener);
        return $this->dm;
    }

    public function getDifferentIdsForDocuments() {
        return array(
            array('123456'),
            array('516ee7636803faea5600090a:path10421'),
        );
    }

    /**
     * @dataProvider getDifferentIdsForDocuments
     */
    public function testPersistListenersAreCalled($id)
    {
        $dm = $this->getDocumentManager();

        $test = new GH560Document();
        $test->id = $id;
        $test->name = 'test';
        $dm->persist($test);
        $dm->flush();
        
        $dm->clear();

        $called = array(
            Events::prePersist => array('Doctrine\ODM\MongoDB\Tests\Functional\Ticket\GH560Document'),
            Events::postPersist => array('Doctrine\ODM\MongoDB\Tests\Functional\Ticket\GH560Document')
        );
        $this->assertEquals($called, $this->listener->called);
        $this->listener->called = array();
    }

    /**
     * @dataProvider getDifferentIdsForDocuments
     */
    public function testDocumentWithCustomIdStrategyIsSavedAndFoundFromDatabase($id)
    {
        $dm = $this->getDocumentManager();

        $test = new GH560Document();
        $test->id = $id;
        $test->name = 'test';
        $dm->persist($test);
        $dm->flush();
        $dm->clear();
        
        $foundDocument = $dm->find(__NAMESPACE__.'\GH560Document', $id);
        $this->assertEquals($id, $foundDocument->id);
    }

    /**
     * @dataProvider getDifferentIdsForDocuments
     */
    public function testUpdateListenersAreCalled($id)
    {
        $dm = $this->getDocumentManager();

        $test = new GH560Document();
        $test->id   = $id;
        $test->name = 'test';
        $dm->persist($test);
        $dm->flush();
        $this->listener->called = array();

        $test->name = 'id should be ' . $id;
        $dm->flush();

        $dm->clear();

        $called = array(
            Events::preUpdate => array('Doctrine\ODM\MongoDB\Tests\Functional\Ticket\GH560Document'),
            Events::postUpdate => array('Doctrine\ODM\MongoDB\Tests\Functional\Ticket\GH560Document')
        );
        $this->assertEquals($called, $this->listener->called);
        $this->listener->called = array();
    }
}

class GH560EventListener
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
class GH560Document
{
    /** @ODM\Id(strategy="NONE") */
    public $id;

    /** @ODM\String */
    public $name;
}
