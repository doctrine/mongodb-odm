<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\Common\EventSubscriber;
use Doctrine\ODM\MongoDB\Events;
use Doctrine\ODM\MongoDB\Event\LifecycleEventArgs;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

class GH560Test extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    /**
     * @dataProvider provideDocumentIds
     */
    public function testPersistListenersAreCalled($id)
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
            [Events::prePersist, __NAMESPACE__ . '\GH560Document'],
            [Events::postPersist, __NAMESPACE__ . '\GH560Document'],
        ];

        $this->assertEquals($called, $listener->called);
    }

    /**
     * @dataProvider provideDocumentIds
     */
    public function testDocumentWithCustomIdStrategyIsSavedAndFoundFromDatabase($id)
    {
        $doc = new GH560Document($id, 'test');
        $this->dm->persist($doc);
        $this->dm->flush();
        $this->dm->clear();

        $doc = $this->dm->find(__NAMESPACE__ . '\GH560Document', $id);
        $this->assertEquals($id, $doc->id);
    }

    /**
     * @dataProvider provideDocumentIds
     */
    public function testUpdateListenersAreCalled($id)
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
            [Events::preUpdate, __NAMESPACE__ . '\GH560Document'],
            [Events::postUpdate, __NAMESPACE__ . '\GH560Document'],
        ];

        $this->assertEquals($called, $listener->called);
    }

    public function provideDocumentIds()
    {
        return [
            [123456],
            ['516ee7636803faea5600090a:path10421'],
        ];
    }
}

class GH560EventSubscriber implements EventSubscriber
{
    public $called;
    public $events;

    public function __construct(array $events)
    {
        $this->called = [];
        $this->events = $events;
    }

    public function getSubscribedEvents()
    {
        return $this->events;
    }

    public function __call($eventName, $args)
    {
        $this->called[] = [$eventName, get_class($args[0]->getDocument())];
    }
}

/** @ODM\Document */
class GH560Document
{
    /** @ODM\Id(strategy="NONE") */
    public $id;

    /** @ODM\String */
    public $name;

    public function __construct($id, $name)
    {
        $this->id = $id;
        $this->name = $name;
    }
}
