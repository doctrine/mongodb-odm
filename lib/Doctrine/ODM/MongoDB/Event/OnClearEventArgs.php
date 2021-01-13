<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Event;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\Persistence\Event\OnClearEventArgs as BaseOnClearEventArgs;

use function assert;

/**
 * Provides event arguments for the onClear event.
 */
final class OnClearEventArgs extends BaseOnClearEventArgs
{
    public function getDocumentManager(): DocumentManager
    {
        $dm = $this->getObjectManager();
        assert($dm instanceof DocumentManager);

        return $dm;
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
