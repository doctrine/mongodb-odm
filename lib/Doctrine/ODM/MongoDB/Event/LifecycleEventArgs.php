<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Event;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\Persistence\Event\LifecycleEventArgs as BaseLifecycleEventArgs;

/**
 * Lifecycle Events are triggered by the UnitOfWork during lifecycle transitions
 * of documents.
 *
 * @template-extends BaseLifecycleEventArgs<DocumentManager>
 */
class LifecycleEventArgs extends BaseLifecycleEventArgs
{
    public function getDocument(): object
    {
        return $this->getObject();
    }

    public function getDocumentManager(): DocumentManager
    {
        return $this->getObjectManager();
    }
}
