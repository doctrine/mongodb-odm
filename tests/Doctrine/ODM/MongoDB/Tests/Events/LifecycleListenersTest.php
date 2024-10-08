<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Events;

use BadMethodCallException;
use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Event\LifecycleEventArgs;
use Doctrine\ODM\MongoDB\Event\PostCollectionLoadEventArgs;
use Doctrine\ODM\MongoDB\Events;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\PersistentCollection\PersistentCollectionInterface;
use Doctrine\ODM\MongoDB\Tests\BaseTestCase;
use PHPUnit\Framework\Assert;

class LifecycleListenersTest extends BaseTestCase
{
    private MyEventListener $listener;

    private function getDocumentManager(): ?DocumentManager
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

    public function testLifecycleListeners(): void
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
        self::assertEquals($called, $this->listener->called);
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
        self::assertEquals($called, $this->listener->called);
        $this->listener->called = [];

        $document = $dm->find(TestDocument::class, $test->id);
        self::assertInstanceOf(PersistentCollectionInterface::class, $document->embedded);
        $document->embedded->initialize();
        $called = [
            Events::preLoad => [TestDocument::class, TestEmbeddedDocument::class],
            Events::postLoad => [TestDocument::class, TestEmbeddedDocument::class],
        ];
        self::assertEquals($called, $this->listener->called);
        $this->listener->called = [];

        $document->embedded[0]->name = 'changed';
        $dm->flush();

        $called = [
            Events::preUpdate => [TestDocument::class, TestEmbeddedDocument::class],
            Events::postUpdate => [TestDocument::class, TestEmbeddedDocument::class],
        ];
        self::assertEquals($called, $this->listener->called);
        $this->listener->called = [];

        $dm->remove($document);
        $dm->flush();

        $called = [
            Events::preRemove => [TestEmbeddedDocument::class, TestDocument::class],
            Events::postRemove => [TestEmbeddedDocument::class, TestDocument::class],
        ];
        self::assertEquals($called, $this->listener->called);
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
        self::assertEquals($called, $this->listener->called);
        $this->listener->called = [];
    }

    public function testMultipleLevelsOfEmbeddedDocsPrePersist(): void
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
        self::assertEquals($called, $this->listener->called);
        $this->listener->called = [];

        $test->image->thumbnails[0]->name = 'ok';
        $dm->flush();
        $called = [
            Events::preUpdate => [TestProfile::class, Image::class, Thumbnail::class],
            Events::postUpdate => [TestProfile::class, Image::class, Thumbnail::class],
        ];
        self::assertEquals($called, $this->listener->called);
        $this->listener->called = [];
    }

    public function testChangeToReferenceFieldTriggersEvents(): void
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

        $document               = $dm->getRepository($document::class)->find($document->id);
        $profile                = $dm->getRepository($profile::class)->find($profile->id);
        $this->listener->called = [];
        $document->profile      = $profile;
        $dm->flush();
        $dm->clear();
        self::assertEquals($called, $this->listener->called, 'Changing ReferenceOne field did not dispatched proper events.');
        $this->listener->called = [];

        $document               = $dm->getRepository($document::class)->find($document->id);
        $profile                = $dm->getRepository($profile::class)->find($profile->id);
        $this->listener->called = [];
        $document->profiles[]   = $profile;
        $dm->flush();
        self::assertEquals($called, $this->listener->called, 'Changing ReferenceMany field did not dispatched proper events.');
        $this->listener->called = [];
    }

    public function testPostCollectionLoad(): void
    {
        $evm = $this->dm->getEventManager();
        $evm->addEventListener([Events::postCollectionLoad], new PostCollectionLoadEventListener());

        $document       = new TestDocument();
        $document->name = 'Maciej';
        $this->dm->persist($document);
        $this->dm->flush();
        $this->dm->clear();

        $document = $this->dm->getRepository($document::class)->find($document->id);
        self::assertInstanceOf(PersistentCollectionInterface::class, $document->embedded);
        $document->embedded->add(new TestEmbeddedDocument('For mock at 1'));
        // mock at 0, despite adding postCollectionLoad will have empty collection
        $document->embedded->initialize();
        $this->dm->flush();
        $this->dm->clear();

        $document = $this->dm->getRepository($document::class)->find($document->id);
        self::assertInstanceOf(PersistentCollectionInterface::class, $document->embedded);
        $document->embedded->add(new TestEmbeddedDocument('Will not be seen'));
        // mock at 1, collection should have 1 element after
        $document->embedded->initialize();
    }
}

class MyEventListener
{
    /** @var array<string, list<class-string>> */
    public array $called = [];

    /** @param array{LifecycleEventArgs} $args */
    public function __call(string $method, array $args): void
    {
        $document                = $args[0]->getDocument();
        $className               = $document::class;
        $this->called[$method][] = $className;
    }
}

/**
 * I have no idea why mock with ->withConsecutive didn't work but it was called 1 additional time for whatever reason
 * it may had, exactly same test code with this class instead just works.
 */
class PostCollectionLoadEventListener
{
    private int $at = 0;

    /** @param PostCollectionLoadEventArgs<int, TestEmbeddedDocument> $e */
    public function postCollectionLoad(PostCollectionLoadEventArgs $e): void
    {
        switch ($this->at++) {
            case 0:
                Assert::assertCount(0, $e->getCollection());
                break;
            case 1:
                Assert::assertCount(1, $e->getCollection());
                Assert::assertEquals(new TestEmbeddedDocument('For mock at 1'), $e->getCollection()[0]);
                break;
            default:
                throw new BadMethodCallException('This was not expected');
        }
    }
}

#[ODM\Document]
class TestDocument
{
    /** @var string|null */
    #[ODM\Id]
    public $id;

    /** @var string|null */
    #[ODM\Field(type: 'string')]
    public $name;

    /** @var Collection<int, TestEmbeddedDocument> */
    #[ODM\EmbedMany(targetDocument: TestEmbeddedDocument::class)]
    public $embedded;

    /** @var Image|null */
    #[ODM\EmbedOne(targetDocument: Image::class)]
    public $image;

    /** @var Collection<int, TestProfile>|array<TestProfile> */
    #[ODM\ReferenceMany(targetDocument: TestProfile::class)]
    public $profiles;

    /** @var TestProfile|null */
    #[ODM\ReferenceOne(targetDocument: TestProfile::class)]
    public $profile;
}

#[ODM\EmbeddedDocument]
class TestEmbeddedDocument
{
    /** @var string */
    #[ODM\Field(type: 'string')]
    public $name;

    public function __construct(string $name = '')
    {
        $this->name = $name;
    }
}


#[ODM\Document]
class TestProfile
{
    /** @var string|null */
    #[ODM\Id]
    public $id;

    /** @var string|null */
    #[ODM\Field(type: 'string')]
    public $name;

    /** @var Image|null */
    #[ODM\EmbedOne(targetDocument: Image::class)]
    public $image;
}

#[ODM\EmbeddedDocument]
class Image
{
    /** @var string */
    #[ODM\Field(type: 'string')]
    public $name;

    /** @var Collection<int, Thumbnail>|array<Thumbnail> */
    #[ODM\EmbedMany(targetDocument: Thumbnail::class)]
    public $thumbnails = [];

    public function __construct(string $name)
    {
        $this->name = $name;
    }
}

#[ODM\EmbeddedDocument]
class Thumbnail
{
    /** @var string */
    #[ODM\Field(type: 'string')]
    public $name;

    public function __construct(string $name)
    {
        $this->name = $name;
    }
}
