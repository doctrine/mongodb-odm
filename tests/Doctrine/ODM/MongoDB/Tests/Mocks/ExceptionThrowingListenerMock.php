<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Mocks;

use Doctrine\Common\EventSubscriber;
use Doctrine\ODM\MongoDB\Event\OnFlushEventArgs;
use Exception;

class ExceptionThrowingListenerMock implements EventSubscriber
{
    public function getSubscribedEvents(): array
    {
        return ['onFlush'];
    }

    public function onFlush(OnFlushEventArgs $args): void
    {
        throw new Exception('This should not happen');
    }
}
