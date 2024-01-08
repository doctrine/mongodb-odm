<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Event;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\Persistence\Event\LifecycleEventArgs as BaseLifecycleEventArgs;
use Doctrine\Persistence\ObjectManager;
use MongoDB\Driver\Session;

/**
 * Lifecycle Events are triggered by the UnitOfWork during lifecycle transitions
 * of documents.
 *
 * @template-extends BaseLifecycleEventArgs<DocumentManager>
 */
class LifecycleEventArgs extends BaseLifecycleEventArgs
{
    public function __construct(
        object $object,
        ObjectManager $objectManager,
        public readonly ?Session $session = null,
    ) {
        parent::__construct($object, $objectManager);
    }

    public function getDocument(): object
    {
        return $this->getObject();
    }

    public function getDocumentManager(): DocumentManager
    {
        return $this->getObjectManager();
    }

    public function isInTransaction(): bool
    {
        return $this->session?->isInTransaction() ?? false;
    }
}
