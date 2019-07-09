<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Events;

use BadMethodCallException;
use Doctrine\ODM\MongoDB\Event\PostCollectionLoadEventArgs;
use Doctrine\ODM\MongoDB\Events;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Tests\BaseTest;
use function get_class;

class LifecycleListenersTest extends BaseTest
{
    /** @var MyEventListener */
    private $listener;

    private function getDocumentManager()
    {
        $this->listener = new MyEventListener();
        $evm            = $this->dm->getEventManager();
        $events         = [
            Events::prePersist,
            Events::postPersist,
            Events::preUpdate,
            Events::postUpdate,
            Events::preLoad,
            Events::postLoad,
            Events::preRemove,
            Events::postRemove,
        ];
        $evm->addEventListener($events, $this->listener);

        return $this->dm;
    }

    public function testLifecycleListeners()
    {
        $dm = $this->getDocumentManager();

        $test       = new TestDocument();
        $test->name = 'test';
        $dm->persist($test);
        $dm->flush();

        $called = [
            Events::prePersist => [TestDocument::class],
            Events::postPersist => [TestDocument::class],
        ];
        $this->assertEquals($called, $this->listener->called);
        $this->listener->called = [];

        $test->embedded[0]       = new TestEmbeddedDocument();
        $test->embedded[0]->name = 'cool';
        $dm->flush();
        $dm->clear();

        $called = [
            Events::prePersist => [TestEmbeddedDocument::class],
            Events::preUpdate => [TestDocument::class],
            Events::postUpdate => [TestDocument::class],
            Events::postPersist => [TestEmbeddedDocument::class],
        ];
        $this->assertEquals($called, $this->listener->called);
        $this->listener->called = [];

        $document = $dm->find(TestDocument::class, $test->id);
        $document->embedded->initialize();
        $called = [
            Events::preLoad => [TestDocument::class, TestEmbeddedDocument::class],
            Events::postLoad => [TestDocument::class, TestEmbeddedDocument::class],
        ];
        $this->assertEquals($called, $this->listener->called);
        $this->listener->called = [];

        $document->embedded[0]->name = 'changed';
        $dm->flush();

        $called = [
            Events::preUpdate => [TestDocument::class, TestEmbeddedDocument::class],
            Events::postUpdate => [TestDocument::class, TestEmbeddedDocument::class],
        ];
        $this->assertEquals($called, $this->listener->called);
        $this->listener->called = [];

        $dm->remove($document);
        $dm->flush();

        $called = [
            Events::preRemove => [TestEmbeddedDocument::class, TestDocument::class],
            Events::postRemove => [TestEmbeddedDocument::class, TestDocument::class],
        ];
        $this->assertEquals($called, $this->listener->called);
        $this->listener->called = [];

        $test                    = new TestDocument();
        $test->name              = 'test';
        $test->embedded[0]       = new TestEmbeddedDocument();
        $test->embedded[0]->name = 'cool';
        $dm->persist($test);
        $dm->flush();
        $this->listener->called = [];

        $test->name = 'cool';
        $dm->flush();

        $dm->clear();

        $called = [
            Events::preUpdate => [TestDocument::class],
            Events::postUpdate => [TestDocument::class],
        ];
        $this->assertEquals($called, $this->listener->called);
        $this->listener->called = [];
    }

    public function testMultipleLevelsOfEmbeddedDocsPrePersist()
    {
        $dm = $this->getDocumentManager();

        $test        = new TestProfile();
        $test->name  = 'test';
        $test->image = new Image('Test Image');
        $dm->persist($test);
        $dm->flush();
        $dm->clear();

        $test                   = $dm->find(TestProfile::class, $test->id);
        $this->listener->called = [];

        $test->image->thumbnails[] = new Thumbnail('Thumbnail #1');

        $dm->flush();
        $called = [
            Events::prePersist => [Thumbnail::class],
            Events::preUpdate => [TestProfile::class, Image::class],
            Events::postUpdate => [TestProfile::class, Image::class],
            Events::postPersist => [Thumbnail::class],
        ];
        $this->assertEquals($called, $this->listener->called);
        $this->listener->called = [];

        $test->image->thumbnails[0]->name = 'ok';
        $dm->flush();
        $called = [
            Events::preUpdate => [TestProfile::class, Image::class, Thumbnail::class],
            Events::postUpdate => [TestProfile::class, Image::class, Thumbnail::class],
        ];
        $this->assertEquals($called, $this->listener->called);
        $this->listener->called = [];
    }

    public function testChangeToReferenceFieldTriggersEvents()
    {
        $dm             = $this->getDocumentManager();
        $document       = new TestDocument();
        $document->name = 'Maciej';
        $dm->persist($document);
        $profile       = new TestProfile();
        $profile->name = 'github';
        $dm->persist($profile);
        $dm->flush();
        $dm->clear();
        $this->listener->called = [];

        $called = [
            Events::preUpdate => [TestDocument::class],
            Events::postUpdate => [TestDocument::class],
        ];

        $document               = $dm->getRepository(get_class($document))->find($document->id);
        $profile                = $dm->getRepository(get_class($profile))->find($profile->id);
        $this->listener->called = [];
        $document->profile      = $profile;
        $dm->flush();
        $dm->clear();
        $this->assertEquals($called, $this->listener->called, 'Changing ReferenceOne field did not dispatched proper events.');
        $this->listener->called = [];

        $document               = $dm->getRepository(get_class($document))->find($document->id);
        $profile                = $dm->getRepository(get_class($profile))->find($profile->id);
        $this->listener->called = [];
        $document->profiles[]   = $profile;
        $dm->flush();
        $this->assertEquals($called, $this->listener->called, 'Changing ReferenceMany field did not dispatched proper events.');
        $this->listener->called = [];
    }

    public function testPostCollectionLoad()
    {
        $evm = $this->dm->getEventManager();
        $evm->addEventListener([Events::postCollectionLoad], new PostCollectionLoadEventListener($this));

        $document       = new TestDocument();
        $document->name = 'Maciej';
        $this->dm->persist($document);
        $this->dm->flush();
        $this->dm->clear();

        $document = $this->dm->getRepository(get_class($document))->find($document->id);
        $document->embedded->add(new TestEmbeddedDocument('For mock at 1'));
        // mock at 0, despite adding postCollectionLoad will have empty collection
        $document->embedded->initialize();
        $this->dm->flush();
        $this->dm->clear();

        $document = $this->dm->getRepository(get_class($document))->find($document->id);
        $document->embedded->add(new TestEmbeddedDocument('Will not be seen'));
        // mock at 1, collection should have 1 element after
        $document->embedded->initialize();
    }
}

class MyEventListener
{
    public $called = [];

    public function __call($method, $args)
    {
        $document                = $args[0]->getDocument();
        $className               = get_class($document);
        $this->called[$method][] = $className;
    }
}

/**
 * I have no idea why mock with ->withConsecutive didn't work but it was called 1 additional time for whatever reason
 * it may had, exactly same test code with this class instead just works.
 */
class PostCollectionLoadEventListener
{
    private $at = 0;
    private $phpunit;

    public function __construct($phpunit)
    {
        $this->phpunit = $phpunit;
    }

    public function postCollectionLoad(PostCollectionLoadEventArgs $e)
    {
        switch ($this->at++) {
            case 0:
                $this->phpunit->assertCount(0, $e->getCollection());
                break;
            case 1:
                $this->phpunit->assertCount(1, $e->getCollection());
                $this->phpunit->assertEquals(new TestEmbeddedDocument('For mock at 1'), $e->getCollection()[0]);
                break;
            default:
                throw new BadMethodCallException('This was not expected');
        }
    }
}

/** @ODM\Document */
class TestDocument
{
    /** @ODM\Id */
    public $id;

    /** @ODM\Field(type="string") */
    public $name;

    /** @ODM\EmbedMany(targetDocument=TestEmbeddedDocument::class) */
    public $embedded;

    /** @ODM\EmbedOne(targetDocument=Image::class) */
    public $image;

    /** @ODM\ReferenceMany(targetDocument=TestProfile::class) */
    public $profiles;

    /** @ODM\ReferenceOne(targetDocument=TestProfile::class) */
    public $profile;
}

/** @ODM\EmbeddedDocument */
class TestEmbeddedDocument
{
    /** @ODM\Field(type="string") */
    public $name;

    public function __construct($name = '')
    {
        $this->name = $name;
    }
}


/** @ODM\Document */
class TestProfile
{
    /** @ODM\Id */
    public $id;

    /** @ODM\Field(type="string") */
    public $name;

    /** @ODM\EmbedOne(targetDocument=Image::class) */
    public $image;
}

/**
 * @ODM\EmbeddedDocument
 */
class Image
{
    /** @ODM\Field(type="string") */
    public $name;

    /** @ODM\EmbedMany(targetDocument=Thumbnail::class) */
    public $thumbnails = [];

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
    /** @ODM\Field(type="string") */
    public $name;

    public function __construct($name)
    {
        $this->name = $name;
    }
}
