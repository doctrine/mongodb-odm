<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\ODM\MongoDB\Event\LifecycleEventArgs;
use Doctrine\ODM\MongoDB\Events;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Proxy\Proxy;
use Doctrine\ODM\MongoDB\Tests\BaseTest;

class GH936Test extends BaseTest
{
    public function testRemoveCascadesThroughProxyDocuments()
    {
        $listener = new GH936Listener();
        $this->dm->getEventManager()->addEventListener(Events::postRemove, $listener);

        $baz = new GH936Document();
        $bar = new GH936Document($baz);
        $foo = new GH936Document($bar);

        $this->dm->persist($foo);
        $this->dm->flush();
        $this->dm->clear();

        $foo = $this->dm->find(GH936Document::class, $foo->id);

        $this->assertInstanceOf(Proxy::class, $foo->ref);

        $this->dm->remove($foo);
        $this->dm->flush();

        $this->assertCount(3, $listener->removed);
        $this->assertNull($this->dm->find(GH936Document::class, $foo->id));
        $this->assertNull($this->dm->find(GH936Document::class, $bar->id));
        $this->assertNull($this->dm->find(GH936Document::class, $baz->id));
    }
}

/** @ODM\Document */
class GH936Document
{
    /** @ODM\Id */
    public $id;

    /** @ODM\ReferenceOne(targetDocument=GH936Document::class, cascade={"persist","remove"}) */
    public $ref;

    public function __construct($ref = null)
    {
        $this->ref = $ref;
    }
}

class GH936Listener
{
    public $removed = [];

    public function postRemove(LifecycleEventArgs $args)
    {
        $this->removed[] = $args->getDocument();
    }
}
