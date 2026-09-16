<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Mocks;

use Doctrine\Common\EventSubscriber;
use Doctrine\ODM\MongoDB\Event\OnFlushEventArgs;

class ReentrantCommitListenerMock implements EventSubscriber
{
    public int $onFlushCallCount = 0;

    public function getSubscribedEvents(): array
    {
        return ['onFlush'];
    }

    public function onFlush(OnFlushEventArgs $args): void
    {
        $this->onFlushCallCount++;
        $args->getDocumentManager()->flush();
    }
}
