<?php

namespace Doctrine\ODM\MongoDB\Tests\Events;

use Doctrine\ODM\MongoDB\ODMEvents;

class LifecycleListenersTest extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    private function getDocumentManager()
    {
        $this->listener = new MyEventListener();
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
        $evm->addEventListener($events, $this->listener);
        return $this->dm;
    }

    public function testLifecycleListeners()
    {
        $dm = $this->getDocumentManager();

        $test = new TestDocument();
        $test->name = 'test';
        $dm->persist($test);
        $dm->flush();

        $called = array(
            ODMEvents::prePersist => array('Doctrine\ODM\MongoDB\Tests\Events\TestDocument'),
            ODMEvents::postPersist => array('Doctrine\ODM\MongoDB\Tests\Events\TestDocument')
        );
        $this->assertEquals($called, $this->listener->called);
        $this->listener->called = array();

        $test->embedded[0] = new TestEmbeddedDocument();
        $test->embedded[0]->name = 'cool';
        $dm->flush();
        $dm->clear();

        $called = array(
            ODMEvents::prePersist => array('Doctrine\ODM\MongoDB\Tests\Events\TestEmbeddedDocument'),
            ODMEvents::preUpdate => array('Doctrine\ODM\MongoDB\Tests\Events\TestDocument'),
            ODMEvents::postUpdate => array('Doctrine\ODM\MongoDB\Tests\Events\TestDocument'),
            ODMEvents::postPersist => array('Doctrine\ODM\MongoDB\Tests\Events\TestEmbeddedDocument')
        );
        $this->assertEquals($called, $this->listener->called);
        $this->listener->called = array();

        $document = $dm->find(__NAMESPACE__.'\TestDocument', $test->id);
        $called = array(
            ODMEvents::preLoad => array('Doctrine\ODM\MongoDB\Tests\Events\TestDocument', 'Doctrine\ODM\MongoDB\Tests\Events\TestEmbeddedDocument'),
            ODMEvents::postLoad => array('Doctrine\ODM\MongoDB\Tests\Events\TestEmbeddedDocument', 'Doctrine\ODM\MongoDB\Tests\Events\TestDocument')
        );
        $this->assertEquals($called, $this->listener->called);
        $this->listener->called = array();

        $document->embedded[0]->name = 'changed';
        $dm->flush();

        $called = array(
            ODMEvents::preUpdate => array('Doctrine\ODM\MongoDB\Tests\Events\TestDocument', 'Doctrine\ODM\MongoDB\Tests\Events\TestEmbeddedDocument'),
            ODMEvents::postUpdate => array('Doctrine\ODM\MongoDB\Tests\Events\TestDocument', 'Doctrine\ODM\MongoDB\Tests\Events\TestEmbeddedDocument')
        );
        $this->assertEquals($called, $this->listener->called);
        $this->listener->called = array();

        $dm->remove($document);
        $dm->flush();

        $called = array(
            ODMEvents::preRemove => array('Doctrine\ODM\MongoDB\Tests\Events\TestDocument', 'Doctrine\ODM\MongoDB\Tests\Events\TestEmbeddedDocument'),
            ODMEvents::postRemove => array('Doctrine\ODM\MongoDB\Tests\Events\TestDocument', 'Doctrine\ODM\MongoDB\Tests\Events\TestEmbeddedDocument')
        );
        $this->assertEquals($called, $this->listener->called);
        $this->listener->called = array();

        $test = new TestDocument();
        $test->name = 'test';
        $test->embedded[0] = new TestEmbeddedDocument();
        $test->embedded[0]->name = 'cool';
        $dm->persist($test);
        $dm->flush();
        $this->listener->called = array();

        $test->name = 'cool';
        $dm->flush();

        $dm->clear();

        $called = array(
            ODMEvents::preUpdate => array('Doctrine\ODM\MongoDB\Tests\Events\TestDocument'),
            ODMEvents::postUpdate => array('Doctrine\ODM\MongoDB\Tests\Events\TestDocument')
        );
        $this->assertEquals($called, $this->listener->called);
        $this->listener->called = array();
    }

    public function testMultipleLevelsOfEmbeddedDocsPrePersist()
    {
        $dm = $this->getDocumentManager();

        $test = new TestProfile();
        $test->name = 'test';
        $test->image = new Image('Test Image');
        $dm->persist($test);
        $dm->flush();
        $dm->clear();

        $test = $dm->find(__NAMESPACE__.'\TestProfile', $test->id);
        $this->listener->called = array();

        $test->image->thumbnails[] = new Image('Thumbnail #1');

        $dm->flush();
        $called = array(
            ODMEvents::prePersist => array('Doctrine\ODM\MongoDB\Tests\Events\Image'),
            ODMEvents::preUpdate => array('Doctrine\ODM\MongoDB\Tests\Events\TestProfile', 'Doctrine\ODM\MongoDB\Tests\Events\Image'),
            ODMEvents::postUpdate => array('Doctrine\ODM\MongoDB\Tests\Events\TestProfile', 'Doctrine\ODM\MongoDB\Tests\Events\Image'),
            ODMEvents::postPersist => array('Doctrine\ODM\MongoDB\Tests\Events\Image')
        );
        $this->assertEquals($called, $this->listener->called);
        $this->listener->called = array();

        $test->image->thumbnails[0]->name = 'ok';
        $dm->flush();
        $called = array(
            ODMEvents::preUpdate => array('Doctrine\ODM\MongoDB\Tests\Events\TestProfile', 'Doctrine\ODM\MongoDB\Tests\Events\Image', 'Doctrine\ODM\MongoDB\Tests\Events\Image'),
            ODMEvents::postUpdate => array('Doctrine\ODM\MongoDB\Tests\Events\TestProfile', 'Doctrine\ODM\MongoDB\Tests\Events\Image', 'Doctrine\ODM\MongoDB\Tests\Events\Image'),
        );
        $this->assertEquals($called, $this->listener->called);
        $this->listener->called = array();
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

    /** @EmbedOne(targetDocument="Image") */
    public $image;
}

/** @EmbeddedDocument */
class TestEmbeddedDocument
{
    /** @String */
    public $name;
}


/** @Document */
class TestProfile
{
    /** @Id */
    public $id;

    /** @String */
    public $name;

    /** @EmbedOne(targetDocument="Image") */
    public $image;
}

/**
 * @EmbeddedDocument
 */
class Image
{
    /** @String */
    public $name;

    /** @EmbedMany(targetDocument="Image") */
    public $thumbnails = array();

    public function __construct($name)
    {
        $this->name = $name;
    }
}