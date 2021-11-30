<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Mocks;

use Doctrine\Common\EventSubscriber;
use Doctrine\ODM\MongoDB\Event\OnFlushEventArgs;
use Doctrine\ODM\MongoDB\Event\PreUpdateEventArgs;

use function spl_object_hash;

class PreUpdateListenerMock implements EventSubscriber
{
    public function getSubscribedEvents(): array
    {
        return [
            'onFlush',
            'preUpdate',
        ];
    }

    public function onFlush(OnFlushEventArgs $args): void
    {
        $uow = $args->getDocumentManager()->getUnitOfWork();
        foreach ($uow->getScheduledDocumentUpdates() as $document) {
            $uow->clearDocumentChangeSet(spl_object_hash($document));
        }
    }

    public function preUpdate(PreUpdateEventArgs $args): void
    {
        return;
    }
}
