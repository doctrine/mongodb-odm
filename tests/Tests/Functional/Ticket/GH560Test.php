<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\Common\EventSubscriber;
use Doctrine\ODM\MongoDB\Event\LifecycleEventArgs;
use Doctrine\ODM\MongoDB\Events;
use Doctrine\ODM\MongoDB\Mapping\Attribute as ODM;
use Doctrine\ODM\MongoDB\Tests\BaseTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

use function get_class;

class GH560Test extends BaseTestCase
{
    #[DataProvider('provideDocumentIds')]
    public function testPersistListenersAreCalled(int|string $id): void
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

    #[DataProvider('provideDocumentIds')]
    public function testDocumentWithCustomIdStrategyIsSavedAndFoundFromDatabase(int|string $id): void
    {
        $doc = new GH560Document($id, 'test');
        $this->dm->persist($doc);
        $this->dm->flush();
        $this->dm->clear();

        $doc = $this->dm->find(GH560Document::class, $id);
        self::assertEquals($id, $doc->id);
    }

    #[DataProvider('provideDocumentIds')]
    public function testUpdateListenersAreCalled(int|string $id): void
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

    public static function provideDocumentIds(): array
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

#[ODM\Document]
class GH560Document
{
    /** @var int|string|null */
    #[ODM\Id(strategy: 'NONE')]
    public $id;

    /** @var string */
    #[ODM\Field(type: 'string')]
    public $name;

    public function __construct(int|string $id, string $name)
    {
        $this->id   = $id;
        $this->name = $name;
    }
}
