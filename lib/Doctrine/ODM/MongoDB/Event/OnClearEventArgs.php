<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Event;

use Doctrine\Common\Persistence\Event\OnClearEventArgs as BaseOnClearEventArgs;
use Doctrine\ODM\MongoDB\DocumentManager;

/**
 * Provides event arguments for the onClear event.
 *
 */
class OnClearEventArgs extends BaseOnClearEventArgs
{
    public function getDocumentManager(): DocumentManager
    {
        return $this->getObjectManager();
    }

    public function getDocumentClass(): ?string
    {
        return $this->getEntityClass();
    }

    /**
     * Returns whether this event clears all documents.
     */
    public function clearsAllDocuments(): bool
    {
        return $this->clearsAllEntities();
    }
}
