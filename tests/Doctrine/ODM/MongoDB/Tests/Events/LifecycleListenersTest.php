<?php

namespace Doctrine\ODM\MongoDB\Tests\Events;

use Doctrine\ODM\MongoDB\ODMEvents;

require_once __DIR__ . '/../../../../../TestInit.php';

class LifecycleListenersTest extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    public function testLifecycleListeners()
    {
        $listener = new MyEventListener();
        $evm = $this->dm->getEventManager();
        $events = array(
            ODMEvents::prePersist,
            ODMEvents::postPersist,
            ODMEvents::preUpdate,
            ODMEvents::postUpdate,
            ODMEvents::preLoad,
            ODMEvents::postLoad,
            ODMEvents::preRemove,
            ODMEvents::postRemove
        );
        $evm->addEventListener($events, $listener);

        $test = new TestDocument();
        $test->name = 'test';
        $this->dm->persist($test);
        $this->dm->flush();

        $called = array(
            ODMEvents::prePersist => array('Doctrine\ODM\MongoDB\Tests\Events\TestDocument'),
            ODMEvents::postPersist => array('Doctrine\ODM\MongoDB\Tests\Events\TestDocument')
        );
        $this->assertEquals($called, $listener->called);
        $listener->called = array();

        $test->embedded[0] = new TestEmbeddedDocument();
        $test->embedded[0]->name = 'cool';
        $this->dm->flush();
        $this->dm->clear();

        $called = array(
            ODMEvents::prePersist => array('Doctrine\ODM\MongoDB\Tests\Events\TestEmbeddedDocument'),
            ODMEvents::preUpdate => array('Doctrine\ODM\MongoDB\Tests\Events\TestDocument'),
            ODMEvents::postUpdate => array('Doctrine\ODM\MongoDB\Tests\Events\TestDocument'),
            ODMEvents::postPersist => array('Doctrine\ODM\MongoDB\Tests\Events\TestEmbeddedDocument')
        );
        $this->assertEquals($called, $listener->called);
        $listener->called = array();

        $document = $this->dm->findOne(__NAMESPACE__.'\TestDocument');
        $called = array(
            ODMEvents::preLoad => array('Doctrine\ODM\MongoDB\Tests\Events\TestDocument', 'Doctrine\ODM\MongoDB\Tests\Events\TestEmbeddedDocument'),
            ODMEvents::postLoad => array('Doctrine\ODM\MongoDB\Tests\Events\TestEmbeddedDocument', 'Doctrine\ODM\MongoDB\Tests\Events\TestDocument')
        );
        $this->assertEquals($called, $listener->called);
        $listener->called = array();

        $document->embedded[0]->name = 'changed';
        $this->dm->flush();

        $called = array(
            ODMEvents::preUpdate => array('Doctrine\ODM\MongoDB\Tests\Events\TestDocument', 'Doctrine\ODM\MongoDB\Tests\Events\TestEmbeddedDocument'),
            ODMEvents::postUpdate => array('Doctrine\ODM\MongoDB\Tests\Events\TestDocument', 'Doctrine\ODM\MongoDB\Tests\Events\TestEmbeddedDocument')
        );
        $this->assertEquals($called, $listener->called);
        $listener->called = array();

        $this->dm->remove($document);
        $this->dm->flush();

        $called = array(
            ODMEvents::preRemove => array('Doctrine\ODM\MongoDB\Tests\Events\TestDocument', 'Doctrine\ODM\MongoDB\Tests\Events\TestEmbeddedDocument'),
            ODMEvents::postRemove => array('Doctrine\ODM\MongoDB\Tests\Events\TestDocument', 'Doctrine\ODM\MongoDB\Tests\Events\TestEmbeddedDocument')
        );
        $this->assertEquals($called, $listener->called);
        $listener->called = array();

        $test = new TestDocument();
        $test->name = 'test';
        $test->embedded[0] = new TestEmbeddedDocument();
        $test->embedded[0]->name = 'cool';
        $this->dm->persist($test);
        $this->dm->flush();
        $listener->called = array();

        $test->name = 'cool';
        $this->dm->flush();

        $this->dm->clear();

        $called = array(
            ODMEvents::preUpdate => array('Doctrine\ODM\MongoDB\Tests\Events\TestDocument'),
            ODMEvents::postUpdate => array('Doctrine\ODM\MongoDB\Tests\Events\TestDocument')
        );
        $this->assertEquals($called, $listener->called);
        $listener->called = array();

    }
}

class MyEventListener
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
class TestDocument
{
    /** @Id */
    public $id;

    /** @String */
    public $name;

    /** @EmbedMany(targetDocument="TestEmbeddedDocument") */
    public $embedded;
}

/** @EmbeddedDocument */
class TestEmbeddedDocument
{
    /** @String */
    public $name;
}