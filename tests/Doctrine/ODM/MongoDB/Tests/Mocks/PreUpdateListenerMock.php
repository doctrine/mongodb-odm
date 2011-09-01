<?php

namespace Doctrine\ODM\MongoDB\Tests\Mocks;

use Doctrine\ODM\MongoDB\Event\OnFlushEventArgs;
use Doctrine\ODM\MongoDB\Event\PreUpdateEventArgs;
use Doctrine\Common\EventSubscriber;

class PreUpdateListenerMock implements EventSubscriber
{
    public function getSubscribedEvents()
    {
        return array(
            'onFlush',
            'preUpdate'
        );
    }

    public function onFlush(OnFlushEventArgs $args)
    {
        $uow = $args->getDocumentManager()->getUnitOfWork();
        foreach ($uow->getScheduledDocumentUpdates() as $document) {
            $uow->clearDocumentChangeSet(spl_object_hash($document));
        }
    }

    public function preUpdate(PreUpdateEventArgs $args)
    {
        return; // fatal error
    }
}