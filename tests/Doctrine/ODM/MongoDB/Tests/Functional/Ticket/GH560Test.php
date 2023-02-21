<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\Common\EventSubscriber;
use Doctrine\ODM\MongoDB\Event\LifecycleEventArgs;
use Doctrine\ODM\MongoDB\Events;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Tests\BaseTest;

use function get_class;

class GH560Test extends BaseTest
{
    /**
     * @param int|string $id
     *
     * @dataProvider provideDocumentIds
     */
    public function testPersistListenersAreCalled($id): void
    {
        $listener = new GH560EventSubscriber([
            Events::prePersist,
            Events::postPersist,
        ]);

        $this->dm->getEventManager()->addEventSubscriber($listener);

        $doc = new GH560Document($id, 'test');
        $this->dm->persist($doc);
        $this->dm->flush();
        $this->dm->clear();

        $called = [
            [Events::prePersist, GH560Document::class],
            [Events::postPersist, GH560Document::class],
        ];

        self::assertEquals($called, $listener->called);
    }

    /**
     * @param int|string $id
     *
     * @dataProvider provideDocumentIds
     */
    public function testDocumentWithCustomIdStrategyIsSavedAndFoundFromDatabase($id): void
    {
        $doc = new GH560Document($id, 'test');
        $this->dm->persist($doc);
        $this->dm->flush();
        $this->dm->clear();

        $doc = $this->dm->find(GH560Document::class, $id);
        self::assertEquals($id, $doc->id);
    }

    /**
     * @param int|string $id
     *
     * @dataProvider provideDocumentIds
     */
    public function testUpdateListenersAreCalled($id): void
    {
        $listener = new GH560EventSubscriber([
            Events::preUpdate,
            Events::postUpdate,
        ]);

        $this->dm->getEventManager()->addEventSubscriber($listener);

        $doc = new GH560Document($id, 'test');
        $this->dm->persist($doc);
        $this->dm->flush();

        $doc->name = 'changed';
        $this->dm->flush();
        $this->dm->clear();

        $called = [
            [Events::preUpdate, GH560Document::class],
            [Events::postUpdate, GH560Document::class],
        ];

        self::assertEquals($called, $listener->called);
    }

    public function provideDocumentIds(): array
    {
        return [
            [123456],
            ['516ee7636803faea5600090a:path10421'],
        ];
    }
}

class GH560EventSubscriber implements EventSubscriber
{
    /** @var array<array{string, class-string}> */
    public $called = [];

    /** @var string[] */
    public $events;

    /** @param string[] $events */
    public function __construct(array $events)
    {
        $this->events = $events;
    }

    public function getSubscribedEvents(): array
    {
        return $this->events;
    }

    /** @param array{LifecycleEventArgs} $args */
    public function __call(string $eventName, array $args): void
    {
        $this->called[] = [$eventName, get_class($args[0]->getDocument())];
    }
}

/** @ODM\Document */
class GH560Document
{
    /**
     * @ODM\Id(strategy="NONE")
     *
     * @var int|string|null
     */
    public $id;

    /**
     * @ODM\Field(type="string")
     *
     * @var string
     */
    public $name;

    /** @param int|string $id */
    public function __construct($id, string $name)
    {
        $this->id   = $id;
        $this->name = $name;
    }
}
