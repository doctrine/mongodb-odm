<?php

namespace Doctrine\ODM\MongoDB\Tests\Events;

use Doctrine\ODM\MongoDB\Events;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

class LifecycleListenersTest extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    private function getDocumentManager()
    {
        $this->listener = new MyEventListener();
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

    public function testLifecycleListeners()
    {
        $dm = $this->getDocumentManager();

        $test = new TestDocument();
        $test->name = 'test';
        $dm->persist($test);
        $dm->flush();

        $called = array(
            Events::prePersist => array('Doctrine\ODM\MongoDB\Tests\Events\TestDocument'),
            Events::postPersist => array('Doctrine\ODM\MongoDB\Tests\Events\TestDocument')
        );
        $this->assertEquals($called, $this->listener->called);
        $this->listener->called = array();

        $test->embedded[0] = new TestEmbeddedDocument();
        $test->embedded[0]->name = 'cool';
        $dm->flush();
        $dm->clear();

        $called = array(
            Events::prePersist => array('Doctrine\ODM\MongoDB\Tests\Events\TestEmbeddedDocument'),
            Events::preUpdate => array('Doctrine\ODM\MongoDB\Tests\Events\TestDocument'),
            Events::postUpdate => array('Doctrine\ODM\MongoDB\Tests\Events\TestDocument'),
            Events::postPersist => array('Doctrine\ODM\MongoDB\Tests\Events\TestEmbeddedDocument')
        );
        $this->assertEquals($called, $this->listener->called);
        $this->listener->called = array();

        $document = $dm->find(__NAMESPACE__.'\TestDocument', $test->id);
        $document->embedded->initialize();
        $called = array(
            Events::preLoad => array('Doctrine\ODM\MongoDB\Tests\Events\TestDocument', 'Doctrine\ODM\MongoDB\Tests\Events\TestEmbeddedDocument'),
            Events::postLoad => array('Doctrine\ODM\MongoDB\Tests\Events\TestDocument', 'Doctrine\ODM\MongoDB\Tests\Events\TestEmbeddedDocument')
        );
        $this->assertEquals($called, $this->listener->called);
        $this->listener->called = array();

        $document->embedded[0]->name = 'changed';
        $dm->flush();

        $called = array(
            Events::preUpdate => array('Doctrine\ODM\MongoDB\Tests\Events\TestDocument', 'Doctrine\ODM\MongoDB\Tests\Events\TestEmbeddedDocument'),
            Events::postUpdate => array('Doctrine\ODM\MongoDB\Tests\Events\TestDocument', 'Doctrine\ODM\MongoDB\Tests\Events\TestEmbeddedDocument')
        );
        $this->assertEquals($called, $this->listener->called);
        $this->listener->called = array();

        $dm->remove($document);
        $dm->flush();

        $called = array(
            Events::preRemove => array('Doctrine\ODM\MongoDB\Tests\Events\TestDocument', 'Doctrine\ODM\MongoDB\Tests\Events\TestEmbeddedDocument'),
            Events::postRemove => array('Doctrine\ODM\MongoDB\Tests\Events\TestDocument', 'Doctrine\ODM\MongoDB\Tests\Events\TestEmbeddedDocument')
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
            Events::preUpdate => array('Doctrine\ODM\MongoDB\Tests\Events\TestDocument'),
            Events::postUpdate => array('Doctrine\ODM\MongoDB\Tests\Events\TestDocument')
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

        $test->image->thumbnails[] = new Thumbnail('Thumbnail #1');

        $dm->flush();
        $called = array(
            Events::prePersist => array('Doctrine\ODM\MongoDB\Tests\Events\Thumbnail'),
            Events::preUpdate => array('Doctrine\ODM\MongoDB\Tests\Events\TestProfile', 'Doctrine\ODM\MongoDB\Tests\Events\Image'),
            Events::postUpdate => array('Doctrine\ODM\MongoDB\Tests\Events\TestProfile', 'Doctrine\ODM\MongoDB\Tests\Events\Image'),
            Events::postPersist => array('Doctrine\ODM\MongoDB\Tests\Events\Thumbnail')
        );
        $this->assertEquals($called, $this->listener->called);
        $this->listener->called = array();

        $test->image->thumbnails[0]->name = 'ok';
        $dm->flush();
        $called = array(
            Events::preUpdate => array('Doctrine\ODM\MongoDB\Tests\Events\TestProfile', 'Doctrine\ODM\MongoDB\Tests\Events\Image', 'Doctrine\ODM\MongoDB\Tests\Events\Thumbnail'),
            Events::postUpdate => array('Doctrine\ODM\MongoDB\Tests\Events\TestProfile', 'Doctrine\ODM\MongoDB\Tests\Events\Image', 'Doctrine\ODM\MongoDB\Tests\Events\Thumbnail'),
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

/** @ODM\Document */
class TestDocument
{
    /** @ODM\Id */
    public $id;

    /** @ODM\String */
    public $name;

    /** @ODM\EmbedMany(targetDocument="TestEmbeddedDocument") */
    public $embedded;

    /** @ODM\EmbedOne(targetDocument="Image") */
    public $image;
}

/** @ODM\EmbeddedDocument */
class TestEmbeddedDocument
{
    /** @ODM\String */
    public $name;
}


/** @ODM\Document */
class TestProfile
{
    /** @ODM\Id */
    public $id;

    /** @ODM\String */
    public $name;

    /** @ODM\EmbedOne(targetDocument="Image") */
    public $image;
}

/**
 * @ODM\EmbeddedDocument
 */
class Image
{
    /** @ODM\String */
    public $name;

    /** @ODM\EmbedMany(targetDocument="Thumbnail") */
    public $thumbnails = array();

    public function __construct($name)
    {
        $this->name = $name;
    }
}

/**
 * @ODM\EmbeddedDocument
 */
class Thumbnail
{
    /** @ODM\String */
    public $name;

    public function __construct($name)
    {
        $this->name = $name;
    }
}