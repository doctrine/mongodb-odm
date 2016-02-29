<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\ODM\MongoDB\Events;
use Doctrine\ODM\MongoDB\Event\LifecycleEventArgs;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

class GH936Test extends \Doctrine\ODM\MongoDB\Tests\BaseTest
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

        $foo = $this->dm->find(GH936Document::CLASSNAME, $foo->id);

        $this->assertInstanceOf('Doctrine\ODM\MongoDB\Proxy\Proxy', $foo->ref);

        $this->dm->remove($foo);
        $this->dm->flush();

        $this->assertCount(3, $listener->removed);
        $this->assertNull($this->dm->find(GH936Document::CLASSNAME, $foo->id));
        $this->assertNull($this->dm->find(GH936Document::CLASSNAME, $bar->id));
        $this->assertNull($this->dm->find(GH936Document::CLASSNAME, $baz->id));
    }
}

/** @ODM\Document */
class GH936Document
{
    const CLASSNAME = __CLASS__;

    /** @ODM\Id */
    public $id;

    /** @ODM\ReferenceOne(targetDocument="GH936Document", cascade={"persist","remove"}) */
    public $ref;

    public function __construct($ref = null)
    {
        $this->ref = $ref;
    }
}

class GH936Listener
{
    public $removed = array();

    public function postRemove(LifecycleEventArgs $args)
    {
        $this->removed[] = $args->getDocument();
    }
}
