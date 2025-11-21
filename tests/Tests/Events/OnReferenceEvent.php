<?php

declare(strict_types=1);

namespace Tests\Events;

use Doctrine\ODM\MongoDB\Event\LifecycleEventArgs;
use Doctrine\ODM\MongoDB\Events;
use Doctrine\ODM\MongoDB\Tests\BaseTestCase;
use Documents\User;

class OnReferenceEvent extends BaseTestCase
{
    public function testOnReferenceEventIsDispatchedByGetReference(): void
    {
        $this->dm->getEventManager()->addEventListener(Events::onReference, $listener = new class {
            public object $eventArgs;

            public function onReference($eventArgs): void
            {
                $this->eventArgs = $eventArgs;
            }
        });

        $this->dm->getReference(User::class, '123456789012345678901234');

        self::assertTrue(isset($listener->eventArgs), 'onReference event was not dispatched');
        self::assertInstanceOf(LifecycleEventArgs::class, $listener->eventArgs);
        self::assertInstanceOf(User::class, $listener->eventArgs->getObject());
        self::assertTrue($this->dm->isUninitializedObject($listener->eventArgs->getObject()));
    }
}
