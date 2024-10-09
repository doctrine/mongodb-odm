<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Event;

use Doctrine\ODM\MongoDB\DocumentManager;
use MongoDB\Driver\Session;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Lifecycle Events are triggered by the UnitOfWork during lifecycle transitions
 * of documents.
 */
class LifecycleEvent extends Event
{
    use HasDocumentManager;

    public function __construct(
        private object $object,
        private DocumentManager $objectManager,
        public readonly ?Session $session = null,
    ) {
    }

    public function getDocument(): object
    {
        return $this->object;
    }

    public function getDocumentManager(): DocumentManager
    {
        return $this->objectManager;
    }

    public function isInTransaction(): bool
    {
        return $this->session?->isInTransaction() ?? false;
    }
}
