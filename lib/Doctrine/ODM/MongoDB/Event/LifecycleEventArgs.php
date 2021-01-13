<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Event;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\Persistence\Event\LifecycleEventArgs as BaseLifecycleEventArgs;

use function assert;

/**
 * Lifecycle Events are triggered by the UnitOfWork during lifecycle transitions
 * of documents.
 */
class LifecycleEventArgs extends BaseLifecycleEventArgs
{
    public function getDocument(): object
    {
        return $this->getObject();
    }

    public function getDocumentManager(): DocumentManager
    {
        $dm = $this->getObjectManager();
        assert($dm instanceof DocumentManager);

        return $dm;
    }
}
