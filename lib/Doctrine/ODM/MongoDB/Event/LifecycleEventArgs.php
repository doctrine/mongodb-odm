<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Event;

use Doctrine\Common\Persistence\Event\LifecycleEventArgs as BaseLifecycleEventArgs;
use Doctrine\ODM\MongoDB\DocumentManager;

/**
 * Lifecycle Events are triggered by the UnitOfWork during lifecycle transitions
 * of documents.
 *
 */
class LifecycleEventArgs extends BaseLifecycleEventArgs
{
    /**
     * Retrieves the associated document.
     *
     * @return object
     */
    public function getDocument()
    {
        return $this->getObject();
    }

    /**
     * Retrieves the associated DocumentManager.
     *
     * @return DocumentManager
     */
    public function getDocumentManager()
    {
        return $this->getObjectManager();
    }
}
