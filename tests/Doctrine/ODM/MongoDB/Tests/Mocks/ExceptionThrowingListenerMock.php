<?php

namespace Doctrine\ODM\MongoDB\Tests\Mocks;

use Doctrine\ODM\MongoDB\Event\OnFlushEventArgs;
use Doctrine\ODM\MongoDB\Event\PreUpdateEventArgs;
use Doctrine\Common\EventSubscriber;

class ExceptionThrowingListenerMock implements EventSubscriber
{
    public function getSubscribedEvents()
    {
        return [
            'onFlush',
        ];
    }

    public function onFlush(OnFlushEventArgs $args)
    {
        throw new \Exception('This should not happen');
    }
}
