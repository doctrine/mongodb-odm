<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\ODM\MongoDB\Event\LifecycleEventArgs;
use Doctrine\ODM\MongoDB\Events;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Tests\BaseTestCase;

class GH936Test extends BaseTestCase
{
    public function testRemoveCascadesThroughProxyDocuments(): void
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

        self::assertTrue(self::isLazyObject($foo->ref));

        $this->dm->remove($foo);
        $this->dm->flush();

        self::assertCount(3, $listener->removed);
        self::assertNull($this->dm->find(GH936Document::class, $foo->id));
        self::assertNull($this->dm->find(GH936Document::class, $bar->id));
        self::assertNull($this->dm->find(GH936Document::class, $baz->id));
    }
}

#[ODM\Document]
class GH936Document
{
    /** @var string|null */
    #[ODM\Id]
    public $id;

    /** @var GH936Document|null */
    #[ODM\ReferenceOne(targetDocument: self::class, cascade: ['persist', 'remove'])]
    public $ref;

    public function __construct(?GH936Document $ref = null)
    {
        $this->ref = $ref;
    }
}

class GH936Listener
{
    /** @var object[] */
    public array $removed = [];

    public function postRemove(LifecycleEventArgs $args): void
    {
        $this->removed[] = $args->getDocument();
    }
}
